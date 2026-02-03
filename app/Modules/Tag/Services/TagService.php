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

namespace App\Modules\Tag\Services;

use App\Modules\Tag\Models\Tag;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TagService
{
    /**
     * 创建标签
     *
     * @param array $data
     * @param User|null $user
     * @return Tag
     */
    public function create(array $data, User $user = null): Tag
    {
        return DB::transaction(function () use ($data, $user) {
            $tag = Tag::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null,
                'description' => $data['description'] ?? null,
                'color' => $data['color'] ?? '#3498db',
                'sort_order' => $data['sort_order'] ?? 0,
                'is_system' => $data['is_system'] ?? false,
                'metadata' => $data['metadata'] ?? null,
            ]);

            return $tag;
        });
    }

    /**
     * 更新标签
     *
     * @param int $tagId
     * @param array $data
     * @return Tag
     */
    public function update(int $tagId, array $data): Tag
    {
        $tag = Tag::findOrFail($tagId);

        $tag->update([
            'name' => $data['name'] ?? $tag->name,
            'slug' => $data['slug'] ?? $tag->slug,
            'description' => $data['description'] ?? $tag->description,
            'color' => $data['color'] ?? $tag->color,
            'sort_order' => $data['sort_order'] ?? $tag->sort_order,
            'is_system' => $data['is_system'] ?? $tag->is_system,
            'metadata' => $data['metadata'] ?? $tag->metadata,
        ]);

        return $tag;
    }

    /**
     * 删除标签
     *
     * @param int $tagId
     * @return bool
     */
    public function delete(int $tagId): bool
    {
        $tag = Tag::findOrFail($tagId);

        // 系统标签不允许删除
        if ($tag->is_system) {
            throw new \Exception('系统标签不允许删除');
        }

        return $tag->delete();
    }

    /**
     * 获取标签详情
     *
     * @param int|string $tagIdOrSlug
     * @return Tag
     */
    public function find($tagIdOrSlug): Tag
    {
        if (is_numeric($tagIdOrSlug)) {
            return Tag::findOrFail($tagIdOrSlug);
        }

        return Tag::where('slug', $tagIdOrSlug)->firstOrFail();
    }

    /**
     * 获取标签列表
     *
     * @param array $params
     * @return LengthAwarePaginator
     */
    public function getTags(array $params = []): LengthAwarePaginator
    {
        $query = Tag::query();

        // 搜索
        if (!empty($params['search'])) {
            $query->where('name', 'like', '%' . $params['search'] . '%')
                  ->orWhere('description', 'like', '%' . $params['search'] . '%');
        }

        // 标签类型筛选
        if (isset($params['is_system'])) {
            $query->where('is_system', (bool) $params['is_system']);
        }

        // 排序
        $sortBy = $params['sort_by'] ?? 'usage_count';
        $sortDirection = $params['sort_direction'] ?? 'desc';

        switch ($sortBy) {
            case 'usage_count':
                $query->orderBy('usage_count', $sortDirection);
                break;
            case 'name':
                $query->orderBy('name', $sortDirection);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortDirection);
                break;
            case 'sort_order':
                $query->orderBy('sort_order', $sortDirection)
                      ->orderBy('usage_count', 'desc');
                break;
            default:
                $query->orderBy('usage_count', 'desc');
        }

        $perPage = min($params['per_page'] ?? 20, 100);

        return $query->paginate($perPage);
    }

    /**
     * 获取热门标签
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopularTags(int $limit = 20): Collection
    {
        return Tag::byUsageCount()
                 ->limit($limit)
                 ->get();
    }

    /**
     * 获取系统标签
     *
     * @return Collection
     */
    public function getSystemTags(): Collection
    {
        return Tag::system()
                 ->bySortOrder()
                 ->get();
    }

    /**
     * 为内容添加标签
     *
     * @param mixed $taggable 可打标签的模型实例
     * @param array $tagIds 标签ID数组
     * @param User|null $user 操作用户
     * @return mixed
     */
    public function attachTags($taggable, array $tagIds, User $user = null)
    {
        return $taggable->attachTags($tagIds, $user);
    }

    /**
     * 从内容移除标签
     *
     * @param mixed $taggable 可打标签的模型实例
     * @param array|null $tagIds 标签ID数组，为null时移除所有标签
     * @return mixed
     */
    public function detachTags($taggable, array $tagIds = null)
    {
        return $taggable->detachTags($tagIds);
    }

    /**
     * 同步内容的标签
     *
     * @param mixed $taggable 可打标签的模型实例
     * @param array $tagIds 标签ID数组
     * @param User|null $user 操作用户
     * @return mixed
     */
    public function syncTags($taggable, array $tagIds, User $user = null)
    {
        return $taggable->syncTags($tagIds, $user);
    }

    /**
     * 获取内容的所有标签
     *
     * @param mixed $taggable 可打标签的模型实例
     * @return Collection
     */
    public function getTaggableTags($taggable): Collection
    {
        return $taggable->tags;
    }

    /**
     * 通过标签获取内容
     *
     * @param int|string $tagIdOrSlug 标签ID或slug
     * @param string $taggableType 内容类型，如 'post', 'article'
     * @param array $params 分页参数
     * @return LengthAwarePaginator
     */
    public function getTaggedContent($tagIdOrSlug, string $taggableType, array $params = []): LengthAwarePaginator
    {
        $tag = $this->find($tagIdOrSlug);

        $method = $this->getTaggableMethod($taggableType);
        $query = $tag->$method();

        // 可以添加额外的筛选条件
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $perPage = min($params['per_page'] ?? 20, 100);

        return $query->paginate($perPage);
    }

    /**
     * 根据内容类型获取对应的关联方法
     *
     * @param string $taggableType
     * @return string
     */
    protected function getTaggableMethod(string $taggableType): string
    {
        return match ($taggableType) {
            'post' => 'posts',
            'article' => 'articles',
            'topic' => 'topics',
            default => 'taggables',
        };
    }

    /**
     * 清理未使用的标签
     *
     * @return int 删除的标签数量
     */
    public function cleanupUnusedTags(): int
    {
        return Tag::where('usage_count', 0)
                 ->where('is_system', false)
                 ->delete();
    }

    /**
     * 重新计算标签使用次数
     *
     * @return void
     */
    public function recalculateUsageCounts(): void
    {
        $tags = Tag::all();

        foreach ($tags as $tag) {
            $count = DB::table('taggables')
                      ->where('tag_id', $tag->id)
                      ->count();

            $tag->update(['usage_count' => $count]);
        }
    }

    /**
     * 根据模型类型获取模型实例
     *
     * @param string $modelType 模型类型
     * @param int $id 模型ID
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModelByType(string $modelType, int $id): \Illuminate\Database\Eloquent\Model
    {
        $modelClass = $this->getModelClass($modelType);

        return $modelClass::findOrFail($id);
    }

    /**
     * 根据模型类型获取模型类名
     *
     * @param string $modelType 模型类型
     * @return string
     */
    public function getModelClass(string $modelType): string
    {
        return match ($modelType) {
            'post' => \App\Modules\Post\Models\Post::class,
            'article' => \App\Modules\Article\Models\Article::class,
            'topic' => \App\Modules\Topic\Models\Topic::class,
            'user' => \App\Modules\User\Models\User::class,
            default => throw new \InvalidArgumentException('不支持的模型类型: ' . $modelType)
        };
    }
}