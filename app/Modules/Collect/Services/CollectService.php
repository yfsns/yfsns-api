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

namespace App\Modules\Collect\Services;

use App\Http\Exceptions\BusinessException;
use App\Modules\Collect\Models\Collect;
use App\Modules\User\Services\UserService;

use function get_class;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CollectService
{
    /**
     * 构造函数.
     */
    public function __construct(
        private UserService $userService
    ) {}
    /**
     * 收藏内容.
     *
     * @param Model       $model  要收藏的模型实例
     * @param string      $type   收藏类型
     * @param null|string $remark 备注
     */
    public function collect(Model $model, string $type = 'default', ?string $remark = null): array
    {
        $userId = $this->userService->getCurrentUserId();

        // 调试信息
        Log::info('收藏请求', [
            'user_id' => $userId,
            'model_id' => $model->id,
            'model_class' => get_class($model),
            'type' => $type,
            'remark' => $remark,
        ]);

        // 获取简单标识符
        $modelType = $this->getModelTypeByClass(get_class($model));

        // 检查是否已收藏
        $collect = Collect::where('user_id', $userId)
            ->where('collectable_id', $model->id)
            ->where('collectable_type', $modelType)
            ->first();

        if ($collect) {
            Log::info('用户已收藏', [
                'user_id' => $userId,
                'collect_id' => $collect->id,
                'collectable_id' => $model->id,
                'collectable_type' => $modelType,
            ]);

            // 如果已经收藏，返回已收藏的状态，而不是抛出异常
            return [
                'message' => '已经收藏过了',
                'collect' => $collect,
                'already_collected' => true,
            ];
        }

        // 创建收藏记录
        $collect = Collect::create([
            'user_id' => $userId,
            'collectable_id' => $model->id,
            'collectable_type' => $modelType, // 使用简单标识符
            'type' => $type,
            'remark' => $remark,
        ]);

        Log::info('收藏成功', [
            'collect_id' => $collect->id,
            'user_id' => $userId,
            'collectable_id' => $model->id,
            'collectable_type' => $modelType,
        ]);

        // 如果模型有收藏计数，则增加计数
        if (method_exists($model, 'incrementCollectCount')) {
            $model->incrementCollectCount();
        }

        // 同步收藏计数（确保数据一致性）
        if (method_exists($model, 'syncCollectCount')) {
            $model->syncCollectCount();
        }

        return [
            'message' => '收藏成功',
            'collect' => $collect,
            'already_collected' => false,
        ];
    }

    /**
     * 取消收藏.
     *
     * @param Model $model 要取消收藏的模型实例
     */
    public function uncollect(Model $model): array
    {
        $userId = $this->userService->getCurrentUserId();

        // 获取简单标识符
        $modelType = $this->getModelTypeByClass(get_class($model));

        $collect = Collect::where('user_id', $userId)
            ->where('collectable_id', $model->id)
            ->where('collectable_type', $modelType)
            ->first();

        if (! $collect) {
            throw new BusinessException('未收藏该内容');
        }

        $collect->delete();

        // 如果模型有收藏计数，则减少计数
        if (method_exists($model, 'decrementCollectCount')) {
            $model->decrementCollectCount();
        }

        // 同步收藏计数（确保数据一致性）
        if (method_exists($model, 'syncCollectCount')) {
            $model->syncCollectCount();
        }

        return [
            'message' => '取消收藏成功',
        ];
    }

    /**
     * 获取用户的收藏列表.
     *
     * @param null|string $type            收藏类型
     * @param null|string $collectableType 被收藏内容的类型
     * @param int         $page            页码
     * @param int         $perPage         每页数量
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserCollects(?string $type = null, ?string $collectableType = null, int $page = 1, int $perPage = 10)
    {
        $userId = $this->userService->getCurrentUserId();
        
        if (! $userId) {
            throw new BusinessException('需要登录才能查看收藏列表');
        }

        $query = Collect::where('user_id', $userId);
        // 暂时注释掉关联加载，看看是否是关联查询的问题
        // ->with(['collectable', 'user:id,nickname']);

        if ($type) {
            $query->where('type', $type);
        }

        if ($collectableType) {
            $query->where('collectable_type', $collectableType);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * 检查是否已收藏.
     *
     * @param Model $model 要检查的模型实例
     */
    public function isCollected(Model $model): bool
    {
        $userId = $this->userService->getCurrentUserId();
        
        // 如果用户未登录，返回 false
        if (! $userId) {
            return false;
        }

        $modelType = $this->getModelTypeByClass(get_class($model));

        return Collect::where('user_id', $userId)
            ->where('collectable_id', $model->id)
            ->where('collectable_type', $modelType)
            ->exists();
    }

    /**
     * 获取内容的收藏数量.
     *
     * @param Model $model 要统计的模型实例
     */
    public function getCollectCount(Model $model): int
    {
        $modelType = $this->getModelTypeByClass(get_class($model));

        return Collect::where('collectable_id', $model->id)
            ->where('collectable_type', $modelType)
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
}
