<?php

/**
 * YFSNS社交网络服务系统
 *
 * Copyright (C) 2025 合肥音符信息科技有限公司
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Modules\Comment\Services;

use App\Modules\Topic\Events\TopicsUpdated;
use App\Http\Exceptions\BusinessException;
use App\Http\Traits\IpRecordTrait;
use App\Modules\Comment\Models\Comment;
use App\Modules\User\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserCommentService
{
    use IpRecordTrait;

    // ========== 常量定义 ==========

    private const CACHE_TTL_SECONDS = 300;
    private const SLOW_QUERY_THRESHOLD = 1.0;

    // ========== 构造函数 ==========

    public function __construct(
        private UserService $userService
    ) {}

    // ========== 公共方法 ==========

    /**
     * 获取完整的评论树（包含主评论和回复）
     */
    public function getCommentTree(array $params, $currentUser = null)
    {
        $targetType = $params['target_type'];
        $targetId = $params['target_id'];

        // 获取所有相关的评论（主评论和回复）
        // 注意：由于Comment.likes()不是真正的关联，我们不能使用with('likes')
        $comments = Comment::query()
            ->with(array_filter($this->getCommentEagerLoads($currentUser), function ($key) {
                return $key !== 'likes'; // 排除likes关联
            }, ARRAY_FILTER_USE_KEY))
            ->where(function ($query) use ($targetType, $targetId) {
                $query->where('target_type', $targetType)
                      ->where('target_id', $targetId);
            })
            ->published()
            ->orderBy('created_at', 'desc') // 主评论按时间倒序
            ->get();

        // 手动加载likes关联（因为Comment.likes()不是真正的关联）
        if ($currentUser) {
            $commentIds = $comments->pluck('id');
            $likes = \App\Modules\Like\Models\Like::where('likeable_type', 'comment')
                ->whereIn('likeable_id', $commentIds)
                ->where('user_id', $currentUser->id)
                ->get()
                ->keyBy('likeable_id');

            // 将likes关联到对应的评论
            $comments->each(function ($comment) use ($likes) {
                $like = $likes->get($comment->id);
                $comment->setRelation('likes', $like ? collect([$like]) : collect());
            });
        } else {
            // 未登录用户设置空的likes关联
            $comments->each(function ($comment) {
                $comment->setRelation('likes', collect());
            });
        }

        // 构建评论树
        return $this->buildCommentTree($comments, $targetType, $targetId);
    }

    /**
     * 获取分页的评论树
     */
    public function getPagedCommentTree(array $params, $currentUser = null)
    {
        $targetType = $params['target_type'];
        $targetId = $params['target_id'];
        $limit = $params['limit'] ?? 10;
        $cursor = $params['cursor'] ?? null;

        // 获取分页的主评论（只获取顶级评论，不包含回复）
        $query = Comment::query()
            ->with(array_filter($this->getCommentEagerLoads($currentUser), function ($key) {
                return $key !== 'likes'; // 排除likes关联
            }, ARRAY_FILTER_USE_KEY))
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->whereNull('parent_id') // 只获取主评论
            ->published()
            ->orderBy('created_at', 'desc');

        // 应用游标分页
        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        $paginator = $query->cursorPaginate($limit, ['*'], 'cursor', $cursor);

        // 为每个主评论加载回复
        $commentsWithReplies = $paginator->getCollection()->map(function ($comment) use ($currentUser) {
            // 获取这个评论的所有回复（限制数量以提高性能）
            $replies = Comment::query()
                ->with(array_filter($this->getCommentEagerLoads($currentUser), function ($key) {
                    return $key !== 'likes';
                }, ARRAY_FILTER_USE_KEY))
                ->where('parent_id', $comment->id)
                ->published()
                ->orderBy('created_at', 'asc')
                ->limit(5) // 限制回复数量
                ->get();

            // 手动加载likes关联
            if ($currentUser) {
                $this->loadLikesForComments(collect([$comment]), $currentUser);
                $this->loadLikesForComments($replies, $currentUser);
            } else {
                $comment->setRelation('likes', collect());
                $replies->each(fn($reply) => $reply->setRelation('likes', collect()));
            }

            $comment->replies = $replies;
            return $comment;
        });

        // 返回分页器，设置处理后的数据（保持原始模型用于cursor分页）
        return $paginator->setCollection($commentsWithReplies);
    }

    /**
     * 为评论集合加载点赞信息
     */
    protected function loadLikesForComments($comments, $currentUser)
    {
        if ($comments->isEmpty()) {
            return;
        }

        $commentIds = $comments->pluck('id');
        $likes = \App\Modules\Like\Models\Like::where('likeable_type', 'comment')
            ->whereIn('likeable_id', $commentIds)
            ->where('user_id', $currentUser->id)
            ->get()
            ->keyBy('likeable_id');

        $comments->each(function ($comment) use ($likes) {
            $like = $likes->get($comment->id);
            $comment->setRelation('likes', $like ? collect([$like]) : collect());
        });
    }

    /**
     * 创建评论
     */
    public function create(array $data, ?Request $request = null): Comment
    {
        $targetType = $data['target_type'] ?? 'post';
        $this->validateTargetExists($data['target_id'], $targetType);

        $startTime = microtime(true);

        $comment = DB::transaction(function () use ($data, $request) {
            $comment = $this->createComment($data, $request);
            $this->processCommentRelations($comment, $data);
            $this->loadCommentRelations($comment);
            $this->clearCommentCache($comment);

            return $comment;
        });

        $this->logSlowQuery($startTime, $data);

        return $comment;
    }

    /**
     * 删除评论
     *
     * @throws BusinessException
     */
    public function deleteComment(Comment $comment): void
    {
        $this->delete($comment);
    }

    /**
     * 点赞评论
     */
    public function like(int $commentId, int $userId): void
    {
        $comment = Comment::findOrFail($commentId);
        
        // 检查是否已点赞
        $existingLike = \App\Modules\Like\Models\Like::where('user_id', $userId)
            ->where('likeable_id', $commentId)
            ->where('likeable_type', 'comment')
            ->first();

        if (!$existingLike) {
            \App\Modules\Like\Models\Like::create([
                'user_id' => $userId,
                'likeable_id' => $commentId,
                'likeable_type' => 'comment',
            ]);
            $comment->increment('like_count');
        }

        $this->clearCommentCache($comment);
    }

    /**
     * 取消点赞
     */
    public function unlike(int $commentId, int $userId): void
    {
        $comment = Comment::findOrFail($commentId);
        
        $deleted = \App\Modules\Like\Models\Like::where('user_id', $userId)
            ->where('likeable_id', $commentId)
            ->where('likeable_type', 'comment')
            ->delete();

        if ($deleted > 0) {
            $comment->decrement('like_count');
        }

        $this->clearCommentCache($comment);
    }

    /**
     * 获取评论数量
     */
    public function count(array $params): int
    {
        $targetType = $params['target_type'] ?? 'post';
        $targetId = (int) ($params['target_id'] ?? 0);
        $cacheKey = "comments:count:{$targetType}:{$targetId}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($targetType, $targetId) {
            return Comment::query()
                ->byTarget($targetType, $targetId)
                ->published()
                ->count();
        });
    }


    // ========== 受保护方法 ==========

    /**
     * 创建评论
     */
    protected function createComment(array $data, ?Request $request): Comment
    {
        $commentData = $data;

        // 设置默认值
        if (!isset($commentData['content_type'])) {
            $commentData['content_type'] = 'text';
        }
        if (!isset($commentData['status'])) {
            // 创建临时评论实例来检查审核配置
            $tempComment = new Comment();
            $commentData['status'] = $tempComment->isContentReviewEnabled()
                ? Comment::STATUS_PENDING
                : Comment::STATUS_PUBLISHED;
        }

        if ($request) {
            $commentData = $this->recordIpInfo($request, $commentData);
        }

        $comment = Comment::create($commentData);

        if ($comment->isPublished()) {
            $this->incrementTargetCommentCount($comment);
        }

        return $comment;
    }

    /**
     * 处理评论关联关系
     */
    protected function processCommentRelations(Comment $comment, array $data): void
    {
        // 处理评论回复通知
        if ($comment->parent_id) {
            $this->handleCommentReplyNotification($comment, $data);
        } else {
            // 处理动态评论通知（顶级评论）
            $this->handlePostCommentNotification($comment, $data);
        }

        // 处理@用户 - 使用新的Mention服务
        if (!empty($data['mentions']) && is_array($data['mentions'])) {
            $mentionService = app(\App\Modules\User\Services\UserMentionService::class);
            $mentionService->createMentions($data['user_id'], $data['mentions'], 'comment', $comment->id);
        }

        if (!empty($data['topics']) && is_array($data['topics'])) {
            event(new TopicsUpdated('comment', $comment->id, $data['topics'], 'sync'));
        }
    }

    /**
     * 处理动态评论通知
     */
    protected function handlePostCommentNotification(Comment $comment, array $data): void
    {
        try {
            // 获取动态
            $targetType = $data['target_type'] ?? 'post';
            if ($targetType !== 'post') {
                return; // 只处理动态评论
            }

            $post = \App\Modules\Post\Models\Post::find($data['target_id']);
            if (!$post) {
                return;
            }

            // 获取动态的作者（接收者）
            $receiver = $post->user;
            if (!$receiver) {
                return;
            }

            // 获取评论者
            $sender = $comment->user;
            if (!$sender) {
                return;
            }

            // 不给自己发通知
            if ($sender->id === $receiver->id) {
                return;
            }

            // 触发动态评论事件
            event(new \App\Modules\Notification\Events\PostCommented(
                $sender,
                $receiver,
                $post,
                $comment
            ));

        } catch (\Exception $e) {
            // 记录错误但不影响评论创建
            \Log::error('动态评论通知发送失败', [
                'comment_id' => $comment->id,
                'post_id' => $data['target_id'] ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 处理评论回复通知
     */
    protected function handleCommentReplyNotification(Comment $comment, array $data): void
    {
        try {
            // 获取父评论
            $parentComment = Comment::find($comment->parent_id);
            if (!$parentComment) {
                return;
            }

            // 获取父评论的作者（接收者）
            $receiver = $parentComment->user;
            if (!$receiver) {
                return;
            }

            // 获取回复者
            $sender = $comment->user;
            if (!$sender) {
                return;
            }

            // 不给自己发通知
            if ($sender->id === $receiver->id) {
                return;
            }

            // 触发评论回复事件
            event(new \App\Modules\Notification\Events\CommentReplied(
                $sender,
                $receiver,
                $parentComment,
                $comment
            ));

        } catch (\Exception $e) {
            // 记录错误但不影响评论创建
            \Log::error('评论回复通知发送失败', [
                'comment_id' => $comment->id,
                'parent_id' => $comment->parent_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 加载评论关联关系
     */
    protected function loadCommentRelations(Comment $comment): void
    {
        $comment->load($this->getCommentEagerLoads());
    }



    /**
     * 删除评论（内部方法，权限已在Controller层检查）
     *
     * @throws BusinessException
     */
    protected function delete(Comment $comment): void
    {
        DB::transaction(function () use ($comment): void {
            $comment->delete();

            if ($comment->isPublished()) {
                $this->decrementTargetCommentCount($comment);
            }

            $this->clearCommentCache($comment);
        });
    }

    /**
     * 增加目标模型的评论数
     */
    protected function incrementTargetCommentCount(Comment $comment): void
    {
        match ($comment->target_type) {
            'post' => \App\Modules\Post\Models\Post::where('id', $comment->target_id)
                ->increment('comment_count'),
            'article' => \App\Modules\Article\Models\Article::where('id', $comment->target_id)
                ->increment('comment_count'),
            default => null, // 评论的评论暂不支持计数
        };
    }

    /**
     * 减少目标模型的评论数
     */
    protected function decrementTargetCommentCount(Comment $comment): void
    {
        match ($comment->target_type) {
            'post' => \App\Modules\Post\Models\Post::where('id', $comment->target_id)
                ->where('comment_count', '>', 0)
                ->decrement('comment_count'),
            'article' => \App\Modules\Article\Models\Article::where('id', $comment->target_id)
                ->where('comment_count', '>', 0)
                ->decrement('comment_count'),
            default => null, // 评论的评论暂不支持计数
        };
    }

    /**
     * 清除缓存
     */
    protected function clearCommentCache(Comment $comment): void
    {
        Cache::forget("comments:{$comment->target_type}:{$comment->target_id}");
        if ($comment->parent_id) {
            Cache::forget("comments:replies:{$comment->parent_id}");
        }
    }



    /**
     * 构建评论树结构
     */
    protected function buildCommentTree(\Illuminate\Database\Eloquent\Collection $comments, string $targetType, int $targetId): \Illuminate\Database\Eloquent\Collection
    {
        // 分离主评论和回复评论
        $mainComments = $comments->where('parent_id', null)->values();
        $replyComments = $comments->where('parent_id', '!=', null)->groupBy('parent_id');

        // 获取当前用户信息
        $currentUserId = $this->userService->getCurrentUserId();

        // 获取目标用户ID（用于标识作者）
        $targetUserId = $this->getTargetUserId($targetType, $targetId);

        // 构建评论树
        $commentTree = $mainComments->map(function ($comment) use ($replyComments, $currentUserId, $targetUserId) {
            // 为评论添加元数据
            $this->enrichSingleComment($comment, $currentUserId, $targetUserId);

            // 添加回复
            $comment->replies = $replyComments->get($comment->id, collect())->map(function ($reply) use ($currentUserId, $targetUserId) {
                $this->enrichSingleComment($reply, $currentUserId, $targetUserId);
                return $reply;
            })->sortBy('created_at')->values(); // 回复按时间正序

            return $comment;
        });

        return $commentTree;
    }

    /**
     * 为单个评论添加元数据
     */
    protected function enrichSingleComment(Comment $comment, ?int $currentUserId, ?int $targetUserId): void
    {
        // 设置目标用户信息
        $comment->target_user_id = $targetUserId;

        // 设置是否已点赞
        $comment->is_liked = false;
        if ($currentUserId) {
            $comment->is_liked = \App\Modules\Like\Models\Like::where('likeable_type', 'comment')
                ->where('likeable_id', $comment->id)
                ->where('user_id', $currentUserId)
                ->exists();
        }

        // 设置点赞数量
        $comment->like_count = \App\Modules\Like\Models\Like::where('likeable_type', 'comment')
            ->where('likeable_id', $comment->id)
            ->count();

        // 设置回复数量（如果是主评论）
        if ($comment->parent_id === null) {
            $comment->reply_count = Comment::where('parent_id', $comment->id)->published()->count();
        }
    }

    /**
     * 获取评论预加载关联
     */
    protected function getCommentEagerLoads($currentUser = null): array
    {
        $currentUserId = $currentUser ? $currentUser->id : null;

        $eagerLoads = [
            'user' => function ($query) {
                $query->withEssentialFields('status');
            },
            'mentions:id,sender_id,receiver_id,username,nickname_at_time',
            'mentions.user' => function ($query) {
                $query->withBasicFields();
            },
            'topics:id,name,description,cover,post_count,follower_count',
        ];

        // 只在有用户信息时才加载likes关联（避免预加载问题）
        if ($currentUserId) {
            $eagerLoads['likes'] = function ($query) use ($currentUserId) {
                $query->where('user_id', $currentUserId)
                      ->select('id', 'likeable_id', 'user_id');
            };
        }

        return $eagerLoads;
    }

    /**
     * 获取目标对象的 user_id
     */
    protected function getTargetUserId(string $targetType, int $targetId): ?int
    {
        return match ($targetType) {
            'post' => \App\Modules\Post\Models\Post::where('id', $targetId)
                ->value('user_id'),
            'article' => \App\Modules\Article\Models\Article::where('id', $targetId)
                ->value('user_id'),
            'comment' => Comment::query()
                ->where('id', $targetId)
                ->value('user_id'),
            default => null,
        };
    }

    /**
     * 验证目标是否存在
     *
     * @throws BusinessException
     */
    protected function validateTargetExists(int $targetId, string $targetType): void
    {
        $exists = match ($targetType) {
            'post' => \App\Modules\Post\Models\Post::where('id', $targetId)->exists(),
            'article' => \App\Modules\Article\Models\Article::where('id', $targetId)->exists(),
            'comment' => Comment::where('id', $targetId)->exists(),
            default => false,
        };

        if (!$exists) {
            throw new BusinessException("评论目标不存在或已被删除", 404);
        }
    }


    /**
     * 记录慢查询
     */
    protected function logSlowQuery(float $startTime, array $data): void
    {
        $duration = microtime(true) - $startTime;
        if ($duration > self::SLOW_QUERY_THRESHOLD) {
            Log::warning('评论创建耗时过长', [
                'duration' => round($duration, 3),
                'user_id' => $data['user_id'] ?? null,
                'target_type' => $data['target_type'] ?? null,
                'target_id' => $data['target_id'] ?? null,
            ]);
        }
    }

}
