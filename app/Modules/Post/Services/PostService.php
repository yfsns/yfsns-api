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

namespace App\Modules\Post\Services;

use App\Modules\Topic\Events\TopicsUpdated;
use App\Modules\User\Events\UserMentionsCreated;
use App\Http\Exceptions\BusinessException;
use App\Http\Traits\IpRecordTrait;
use App\Http\Traits\SensitiveWordFilterTrait;
use App\Modules\File\Models\File;
use App\Modules\Post\Models\Post;
use App\Modules\Post\Queries\PostQuery;
use App\Modules\User\Models\User;
use App\Modules\User\Services\UserService;
use Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

use function count;
use function in_array;
use function is_array;

class PostService
{
    use IpRecordTrait, SensitiveWordFilterTrait;

    /**
     * 构造函数.
     */
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * 创建动态
     *
     * @param array $data   动态数据
     * @param int   $userId 用户ID
     */
    public function create(array $data, int $userId): Post
    {
        $startTime = microtime(true);

        $result = DB::transaction(function () use ($data, $userId) {
            // 处理位置信息：查找或创建位置记录
            $locationId = null;
            if (!empty($data['location'])) {
                $locationId = $this->findOrCreateLocation($data['location']);
            }

            // 过滤敏感词
            $filteredContent = $this->filterSensitiveWords($data['content'], 'post');

            // 先创建动态实例（不设置状态）
            $postData = [
                'user_id' => $userId,
                'title' => $data['title'] ?? '',
                'content' => $filteredContent,
                'type' => $data['type'],
                'visibility' => $data['visibility'],
                'repost_id' => $data['repost_id'] ?? null,  // 支持转发
                'location_id' => $locationId,
                'device' => request()->userAgent(),
            ];

            // 记录IP信息
            $postData = $this->recordIpInfo(request(), $postData);

            // 创建动态实例
            $post = Post::create($postData);

            // 检查审核开关，决定状态和是否触发审核事件
            if ($post->isContentReviewEnabled()) {
                // 审核开关开启：设置为待审核状态，触发审核事件
                $post->status = Post::STATUS_PENDING;
                $post->save();

                // 触发审核事件，让审核模块决定是AI审核还是人工审核
                \App\Modules\Review\Events\ContentPendingAudit::dispatch('post', $post->id);
            } else {
                // 审核开关关闭：直接设置为已发布状态，跳过审核流程
                $post->status = Post::STATUS_PUBLISHED;
                $post->audited_at = now();
                $post->published_at = now();
                $post->save();

                // 如果是转发动态，增加原动态的转发计数
                if ($post->repost_id) {
                    $originalPost = Post::find($post->repost_id);
                    if ($originalPost) {
                        $originalPost->incrementRepostCount();
                    }
                }
            }

            // 如果有 file_ids，则关联文件（文件验证已在 Request 中完成）
            if (! empty($data['file_ids'])) {
                $this->attachFilesToPost($post, $data, $userId);
            }

            // 处理@用户 - 触发事件让User模块处理
            if (! empty($data['mentions']) && is_array($data['mentions'])) {
                event(new UserMentionsCreated($userId, $data['mentions'], 'post', $post->id));
            }

            // 处理话题标签 - 触发事件让Topic模块处理
            if (! empty($data['topics']) && is_array($data['topics'])) {
                event(new TopicsUpdated('post', $post->id, $data['topics'], 'sync'));
            }

            return $post;
        });

        // 性能监控
        $duration = microtime(true) - $startTime;
        if ($duration > 2.0) { // 记录超过2秒的慢操作（动态创建可能涉及更多处理）
            Log::warning('动态创建耗时过长', [
                'duration' => round($duration, 3),
                'user_id' => $userId,
                'type' => $data['type'] ?? null,
                'has_files' => !empty($data['file_ids'])
            ]);
        }

        return $result;
    }

    /**
     * 获取动态详情.
     *
     * @param int $id 动态ID
     *
     * @throws BusinessException
     */
    public function getDetail(int $id): Post
    {
            $post = Post::findOrFail($id);

            return $post;
    }




    /**
     * 获取动态列表.
     */
    public function getUnifiedPosts(array $params): array
    {
        // 声明式查询构建：使用超轻量级关联加载提升性能
        return PostQuery::build($params)
            ->withUltraLightRelations()  // 使用超轻量级关联加载
            ->applyFilters()
            ->applySorting()
            ->paginateWithCursor()
            ->cacheCount()
            ->enrichUserStatus()
            ->toResponse();
    }

    /**
     * 更新动态
     *
     * @param int   $id   动态ID
     * @param array $data 更新数据
     */
    public function update(int $id, array $data): Post
    {
        return DB::transaction(function () use ($id, $data) {
            $post = Post::findOrFail($id);

            // 处理位置信息
            if (! empty($data['location']) && is_array($data['location'])) {
                $post->location_id = $this->findOrCreateLocation($data['location']);
            }

            // 更新基础字段（保持与创建逻辑一致）
            if (array_key_exists('content', $data)) {
                $post->content = $this->filterSensitiveWords($data['content'] ?? '', 'post');
            }

            if (array_key_exists('title', $data)) {
                $post->title = $data['title'] ?? '';
            }

            if (isset($data['type'])) {
                $post->type = $data['type'];
            }

            if (isset($data['visibility'])) {
                $post->visibility = (int) $data['visibility'];
            }

            if (isset($data['status'])) {
                $post->status = (int) $data['status'];
            }

            $post->save();

            // 更新文件关联（如果提供了 file_ids）
            if (isset($data['file_ids']) && is_array($data['file_ids'])) {
                $this->attachFilesToPost($post, $data, $post->user_id);
            }

            // 处理 @ 用户
            if (isset($data['mentions']) && is_array($data['mentions'])) {
                event(new UserMentionsCreated($post->user_id, $data['mentions'], 'post', $post->id));
            }

            // 处理话题标签
            if (isset($data['topics']) && is_array($data['topics'])) {
                event(new TopicsUpdated('post', $post->id, $data['topics'], 'sync'));
            }

            return $post;
        });
    }

    /**
     * 删除动态（软删除）
     *
     * @param int $id 动态ID
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $post = Post::findOrFail($id);

            // 如果是转发动态，且已发布过，则需要减少原动态的转发计数
            if ($post->repost_id && $post->status === Post::STATUS_PUBLISHED) {
                $originalPost = $post->originalPost ?? Post::find($post->repost_id);
                if ($originalPost) {
                    $originalPost->decrementRepostCount();
                }
            }

            // 软删除动态本身
            return (bool) $post->delete();
        });
    }

    /**
     * 应用动态可见性过滤.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    protected function applyVisibilityFilter($query, ?int $currentUserId, ?int $targetUserId = null): void
    {
        // 构建可见性过滤条件
        $query->where(function ($q) use ($currentUserId): void {
            // 情况1: 公开动态 - 所有人可见
            $q->where('visibility', Post::VISIBILITY_PUBLIC);
            
            if ($currentUserId) {
                // 情况2: 自己的动态 - 无论可见性都能看到
                $q->orWhere('user_id', $currentUserId);
                
                // 情况3: 粉丝可见动态 - 当前用户是发布者的粉丝
                $q->orWhere(function ($subQ) use ($currentUserId): void {
                    $subQ->where('visibility', Post::VISIBILITY_FOLLOWERS)
                        ->whereExists(function ($existsQ) use ($currentUserId): void {
                            $existsQ->select(DB::raw(1))
                                ->from('user_follows')
                                ->where('follower_id', $currentUserId)
                                ->whereColumn('following_id', 'posts.user_id');
                        });
                });
                
                // 情况4: 好友可见动态 - 当前用户是发布者的好友
                $q->orWhere(function ($subQ) use ($currentUserId): void {
                    $subQ->where('visibility', Post::VISIBILITY_FRIENDS)
                        ->whereExists(function ($existsQ) use ($currentUserId): void {
                            $existsQ->select(DB::raw(1))
                                ->from('user_friends')
                                ->where(function ($friendQ) use ($currentUserId): void {
                                    $friendQ->where('user_id', $currentUserId)
                                        ->whereColumn('friend_id', 'posts.user_id');
                                })->orWhere(function ($friendQ) use ($currentUserId): void {
                                    $friendQ->where('friend_id', $currentUserId)
                                        ->whereColumn('user_id', 'posts.user_id');
                                });
                        });
                });
                
                // 情况5: 仅自己可见动态 - 只有发布者能看到
                // 这已经被情况2覆盖，因为user_id会匹配
            }
        });
    }

    /**
     * 为动态添加用户相关状态
     *
     * @param \Illuminate\Database\Eloquent\Collection $posts
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function enrichPostsWithUserStatus($posts, ?int $currentUserId)
    {

        // 为每个动态设置状态
        $posts->each(function ($post) use ($currentUserId): void {
            // 设置点赞和收藏状态
            if ($currentUserId) {
                $post->isLiked = $post->likes->contains('user_id', $currentUserId);
                $post->isCollected = $post->collects->contains('user_id', $currentUserId);
            } else {
                $post->isLiked = false;
                $post->isCollected = false;
            }

            // 权限检查现在在PostResource中通过Policy进行

        });

        return $posts;
    }

    /**
     * 获取模块名称.
     */
    protected function getModuleName(): string
    {
        return 'post';
    }


    /**
     * 转发动态
     *
     * @param int         $postId  原动态ID
     * @param null|string $content 转发时添加的内容
     */
    public function repost(int $postId, ?string $content = null): Post
    {
        $originalPost = Post::findOrFail($postId);

        // 使用事务确保数据一致性
        return DB::transaction(function () use ($originalPost, $content) {
            // 准备转发动态数据
            // 转发就是普通动态，只是包含了原动态的引用（repost_id）
            // content 存储转发者添加的转发理由（可选），原动态内容通过 originalPost 关联获取
            $repostData = [
                'user_id' => $this->userService->getCurrentUserId(),
                'repost_id' => $originalPost->id,  // 引用原动态
                'content' => $content ?? '',         // 转发者添加的转发理由（可选），如果为 null 则使用空字符串
                'type' => Post::TYPE_POST,          // 转发就是普通动态，不是独立的类型
                'visibility' => $originalPost->visibility ?? Post::VISIBILITY_PUBLIC,
                'location_id' => $originalPost->location_id, // 继承原动态的位置
                'status' => (new Post())->isContentReviewEnabled()
                    ? Post::STATUS_PENDING
                    : Post::STATUS_PUBLISHED,
                'device' => request()->userAgent(),
            ];

            // 记录IP信息
            $repostData = $this->recordIpInfo(request(), $repostData);

            // 创建转发动态
            $repost = Post::create($repostData);

            Log::info('转发动态创建成功', [
                'repost_id' => $repost->id,
                'original_post_id' => $originalPost->id,
                'status' => $repost->status,
            ]);

            // 增加原动态的转发计数（仅当转发动态已发布时）
            if ($repost->status === Post::STATUS_PUBLISHED) {
                $originalPost->incrementRepostCount();
            }

            return $repost;
        });
    }

    /**
     * 取消转发.
     *
     * @param int $postId 转发动态ID
     */
    public function unrepost(int $postId): bool
    {
        return DB::transaction(function () use ($postId) {
            // 转发就是普通动态，通过 repost_id 不为空来识别
            $repost = Post::where('user_id', $this->userService->getCurrentUserId())
                ->where('id', $postId)
                ->whereNotNull('repost_id')
                ->firstOrFail();

            // 减少原动态的转发计数（仅当转发动态已发布时）
            if ($repost->originalPost && $repost->status === Post::STATUS_PUBLISHED) {
                $repost->originalPost->decrementRepostCount();
            }

            // 删除转发动态（软删除）
            return $repost->delete();
        });
    }

    /**
     * 获取动态的转发列表.
     *
     * @param int $postId  动态ID
     * @param int $page    页码
     * @param int $perPage 每页数量
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getRepostsByOriginalPost(int $postId, int $page = 1, int $perPage = 10)
    {
        return Post::where('repost_id', $postId)
            ->whereNull('deleted_at')
            ->published() // 使用 scope
            ->with([
                'user' => function ($query) {
                    $query->withBasicFields();
                },
                // 不需要加载 originalPost，因为原动态就是当前查询的 postId
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * 获取用户的转发列表.
     *
     * @param int $userId  用户ID
     * @param int $page    页码
     * @param int $perPage 每页数量
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getRepostsByUser(int $userId, int $page = 1, int $perPage = 10)
    {
        // 转发就是普通动态，通过 repost_id 不为空来识别
        return Post::where('user_id', $userId)
            ->whereNotNull('repost_id')
            ->published() // 使用 scope
            ->with(['originalPost.user' => function ($query) {
                $query->withBasicFields();
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * 查找或创建位置记录
     *
     * @param array $locationData 位置数据
     * @return int|null 位置ID
     */
    protected function findOrCreateLocation(array $locationData): ?int
    {
        if (empty($locationData['latitude']) || empty($locationData['longitude'])) {
            return null;
        }

        // 使用 Location 模型的 findOrCreate 方法
        $location = \App\Modules\Location\Models\Location::findOrCreate($locationData);

        return $location->id;
    }

    /**
     * 将文件关联到动态
     *
     * @param Post  $post   动态实例
     * @param array $data   请求数据（包含file_ids和cover_id）
     * @param int   $userId 用户ID
     */
    protected function attachFilesToPost(Post $post, array $data, int $userId): void
    {
        $fileIds = $data['file_ids'] ?? [];
        $coverId = $data['cover_id'] ?? null;

        if (empty($fileIds)) {
            return;
        }

        // 验证cover_id是否在file_ids中
        if ($coverId && !in_array($coverId, $fileIds)) {
            throw new BusinessException('封面文件必须在文件列表中');
        }

        // 获取可关联的文件
        $files = File::whereIn('id', $fileIds)
            ->where('user_id', $userId)
            ->whereNull('module_id')
            ->whereNull('deleted_at')
            ->get();

        if ($files->isEmpty()) {
            return;
        }

        // 构建关联数据 - 前端指定封面，后端直接使用
        $attachments = [];
        foreach ($files as $index => $file) {
            $attachments[$file->id] = [
                'type' => ($file->id == $coverId) ? 'cover' : 'content',
                'sort' => $index,
            ];
        }

        // 批量操作
        DB::transaction(function () use ($post, $files, $attachments) {
            $post->files()->sync($attachments);

            // 批量更新文件模块信息
            File::whereIn('id', $files->pluck('id'))->update([
                'module' => 'post',
                'module_id' => $post->id,
            ]);
        });
    }


}

