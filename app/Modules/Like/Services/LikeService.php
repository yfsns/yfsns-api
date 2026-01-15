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

namespace App\Modules\Like\Services;

use App\Http\Exceptions\BusinessException;
use App\Modules\Like\Models\Like;
use App\Modules\User\Models\User;
use App\Modules\User\Services\UserService;

use function get_class;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class LikeService
{
    /**
     * 构造函数.
     */
    public function __construct(
        private UserService $userService
    ) {}
    /**
     * 点赞内容.
     *
     * @param Model  $model 要点赞的模型实例
     * @param string $type  点赞类型
     */
    public function like(Model $model, string $type = 'default'): array
    {
        $userId = $this->userService->getCurrentUserId();

        // 调试信息
        Log::info('点赞请求', [
            'user_id' => $userId,
            'model_id' => $model->id,
            'model_class' => get_class($model),
            'type' => $type,
        ]);

        // 获取简单标识符
        $modelType = $this->getModelTypeByClass(get_class($model));

        // 检查是否已点赞
        $like = Like::where('user_id', $userId)
            ->where('likeable_id', $model->id)
            ->where('likeable_type', $modelType)
            ->first();

        if ($like) {
            Log::info('用户已点赞', [
                'user_id' => $userId,
                'like_id' => $like->id,
                'likeable_id' => $model->id,
                'likeable_type' => $modelType,
            ]);

            // 如果已经点赞，返回已点赞的状态，而不是抛出异常
            return [
                'message' => '已经点赞过了',
                'like' => $like,
                'already_liked' => true,
            ];
        }

        // 创建点赞记录
        $like = Like::create([
            'user_id' => $userId,
            'likeable_id' => $model->id,
            'likeable_type' => $modelType, // 使用简单标识符
            'type' => $type,
        ]);

        Log::info('点赞成功', [
            'like_id' => $like->id,
            'user_id' => $userId,
            'likeable_id' => $model->id,
            'likeable_type' => $modelType,
        ]);

        // 如果模型有点赞计数，则增加计数
        if (method_exists($model, 'incrementLikeCount')) {
            $model->incrementLikeCount();
        } else {
            // 直接更新like_count字段
            if (Schema::hasColumn($model->getTable(), 'like_count')) {
                $model->increment('like_count');
                $model->refresh();
            }
        }

        // 同步点赞计数（确保数据一致性）
        if (method_exists($model, 'syncLikeCount')) {
            $model->syncLikeCount();
        }

        // 触发点赞事件通知
        $this->triggerLikeEvent($model, $userId);

        return [
            'message' => '点赞成功',
            'like' => $like,
            'already_liked' => false,
        ];
    }

    /**
     * 取消点赞.
     *
     * @param Model $model 要取消点赞的模型实例
     */
    public function unlike(Model $model): array
    {
        $userId = $this->userService->getCurrentUserId();

        // 获取简单标识符
        $modelType = $this->getModelTypeByClass(get_class($model));

        $like = Like::where('user_id', $userId)
            ->where('likeable_id', $model->id)
            ->where('likeable_type', $modelType)
            ->first();

        if (! $like) {
            throw new BusinessException('未点赞该内容');
        }

        $like->delete();

        // 如果模型有点赞计数，则减少计数
        if (method_exists($model, 'decrementLikeCount')) {
            $model->decrementLikeCount();
        } else {
            // 直接更新like_count字段
            if (Schema::hasColumn($model->getTable(), 'like_count')) {
                $model->decrement('like_count');
                $model->refresh();
            }
        }

        // 同步点赞计数（确保数据一致性）
        if (method_exists($model, 'syncLikeCount')) {
            $model->syncLikeCount();
        }

        return [
            'message' => '取消点赞成功',
        ];
    }

    /**
     * 获取用户的点赞列表.
     *
     * @param null|string $type         点赞类型
     * @param null|string $likeableType 被点赞内容的类型
     * @param int         $page         页码
     * @param int         $perPage      每页数量
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserLikes(?string $type = null, ?string $likeableType = null, int $page = 1, int $perPage = 10)
    {
        $userId = $this->userService->getCurrentUserId();
        
        if (! $userId) {
            throw new BusinessException('需要登录才能查看点赞列表');
        }

        $query = Like::where('user_id', $userId)
            ->with(['likeable', 'user:id,nickname']);

        if ($type) {
            $query->where('type', $type);
        }

        if ($likeableType) {
            $query->where('likeable_type', $likeableType);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * 检查是否已点赞.
     *
     * @param Model $model 要检查的模型实例
     */
    public function isLiked(Model $model): bool
    {
        $userId = $this->userService->getCurrentUserId();
        
        // 如果用户未登录，返回 false
        if (! $userId) {
            return false;
        }

        $modelType = $this->getModelTypeByClass(get_class($model));

        return Like::where('user_id', $userId)
            ->where('likeable_id', $model->id)
            ->where('likeable_type', $modelType)
            ->exists();
    }

    /**
     * 获取内容的点赞数量.
     *
     * @param Model $model 要统计的模型实例
     */
    public function getLikeCount(Model $model): int
    {
        $modelType = $this->getModelTypeByClass(get_class($model));

        return Like::where('likeable_id', $model->id)
            ->where('likeable_type', $modelType)
            ->count();
    }

    /**
     * 根据类型和ID获取模型实例.
     *
     * @param string $modelType 模型类型
     * @param int    $id        模型ID
     *
     * @throws InvalidArgumentException
     */
    public function getModelByType(string $modelType, int $id): Model
    {
        $modelClass = $this->getModelClass($modelType);

        return $modelClass::findOrFail($id);
    }

    /**
     * 根据模型类名获取简单标识符.
     *
     * @param string $modelClass 模型类名
     *
     * @throws InvalidArgumentException
     */
    public function getModelTypeByClass(string $modelClass): string
    {
        return match ($modelClass) {
            \App\Modules\Post\Models\Post::class => 'post',
            \App\Modules\Comment\Models\Comment::class => 'comment',
            \App\Modules\Topic\Models\Topic::class => 'topic',
            \App\Modules\User\Models\User::class => 'user',
            \App\Modules\Forum\Models\ForumThread::class => 'forum_thread',
            \App\Modules\Forum\Models\ForumThreadReply::class => 'forum_threadreply',
            \App\Modules\Article\Models\Article::class => 'article',
            default => throw new InvalidArgumentException('不支持的模型类型: ' . $modelClass)
        };
    }

    /**
     * 根据类型获取模型类名.
     *
     * @param string $modelType 模型类型（应该已经是下划线格式，由 Request 层处理）
     *
     * @throws InvalidArgumentException
     */
    public function getModelClass(string $modelType): string
    {
        return match ($modelType) {
            'post' => \App\Modules\Post\Models\Post::class,
            'comment' => \App\Modules\Comment\Models\Comment::class,
            'topic' => \App\Modules\Topic\Models\Topic::class,
            'user' => \App\Modules\User\Models\User::class,
            'forum_thread' => \App\Modules\Forum\Models\ForumThread::class,
            'forum_threadreply' => \App\Modules\Forum\Models\ForumThreadReply::class,
            'article' => \App\Modules\Article\Models\Article::class,
            default => throw new InvalidArgumentException('不支持的模型类型: ' . $modelType)
        };
    }

    /**
     * 获取支持的模型类型列表.
     */
    public function getSupportedModelTypes(): array
    {
        return ['post', 'comment', 'topic', 'user', 'forum_thread', 'forum_threadreply', 'article'];
    }

    /**
     * 触发点赞事件通知
     */
    protected function triggerLikeEvent(Model $model, int $userId): void
    {
        $sender = User::find($userId);

        // 只有当点赞者和被点赞者不是同一个人时才发送通知
        if (!$sender || $sender->id === $model->user_id) {
            return;
        }

        // 根据模型类型触发不同的事件
        if ($model instanceof \App\Modules\Post\Models\Post) {
            $receiver = $model->user;
            if ($receiver) {
                event(new \App\Modules\Notification\Events\PostLiked($sender, $receiver, $model));
            }
        }
        // 可以在这里添加其他模型类型的通知事件
        // elseif ($model instanceof Comment) {
        //     // 评论点赞通知
        // }
    }
}
