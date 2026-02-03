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

namespace App\Modules\Category\Services;

use App\Modules\Category\Models\Category;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    /**
     * 创建分类
     *
     * @param array $data
     * @param User|null $user
     * @return Category
     */
    public function create(array $data, User $user = null): Category
    {
        // 验证父分类是否存在且不形成循环引用
        if (!empty($data['parent_id'])) {
            $this->validateParentCategory($data['parent_id'], $data['parent_id']);
        }

        return DB::transaction(function () use ($data) {
            $category = Category::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null,
                'description' => $data['description'] ?? null,
                'icon' => $data['icon'] ?? null,
                'color' => $data['color'] ?? '#3498db',
                'parent_id' => $data['parent_id'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
                'is_system' => $data['is_system'] ?? false,
                'metadata' => $data['metadata'] ?? null,
            ]);

            return $category;
        });
    }

    /**
     * 更新分类
     *
     * @param int $categoryId
     * @param array $data
     * @return Category
     */
    public function update(int $categoryId, array $data): Category
    {
        $category = Category::findOrFail($categoryId);

        // 验证父分类
        if (isset($data['parent_id']) && $data['parent_id'] !== $category->parent_id) {
            if (!empty($data['parent_id'])) {
                $this->validateParentCategory($data['parent_id'], $categoryId);
            }
        }

        $category->update([
            'name' => $data['name'] ?? $category->name,
            'slug' => $data['slug'] ?? $category->slug,
            'description' => $data['description'] ?? $category->description,
            'icon' => $data['icon'] ?? $category->icon,
            'color' => $data['color'] ?? $category->color,
            'parent_id' => $data['parent_id'] ?? $category->parent_id,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
            'is_active' => $data['is_active'] ?? $category->is_active,
            'is_system' => $data['is_system'] ?? $category->is_system,
            'metadata' => $data['metadata'] ?? $category->metadata,
        ]);

        return $category;
    }

    /**
     * 删除分类
     *
     * @param int $categoryId
     * @return bool
     */
    public function delete(int $categoryId): bool
    {
        $category = Category::findOrFail($categoryId);

        // 系统分类不允许删除
        if ($category->is_system) {
            throw new \Exception('系统分类不允许删除');
        }

        // 检查是否有子分类
        if ($category->children()->count() > 0) {
            throw new \Exception('该分类下还有子分类，请先删除子分类');
        }

        // 检查是否被内容使用
        if ($category->categorizables()->count() > 0) {
            throw new \Exception('该分类正在被内容使用，请先移除关联');
        }

        return $category->delete();
    }

    /**
     * 获取分类详情
     *
     * @param int|string $categoryIdOrSlug
     * @param array $with 关联加载
     * @return Category
     */
    public function find($categoryIdOrSlug, array $with = []): Category
    {
        $query = Category::with($with);

        if (is_numeric($categoryIdOrSlug)) {
            return $query->findOrFail($categoryIdOrSlug);
        }

        return $query->where('slug', $categoryIdOrSlug)->firstOrFail();
    }

    /**
     * 获取分类列表
     *
     * @param array $params
     * @return LengthAwarePaginator
     */
    public function getCategories(array $params = []): LengthAwarePaginator
    {
        $query = Category::query();

        // 搜索
        if (!empty($params['search'])) {
            $query->where('name', 'like', '%' . $params['search'] . '%')
                  ->orWhere('description', 'like', '%' . $params['search'] . '%');
        }

        // 状态筛选
        if (isset($params['is_active'])) {
            $query->where('is_active', (bool) $params['is_active']);
        }

        if (isset($params['is_system'])) {
            $query->where('is_system', (bool) $params['is_system']);
        }

        // 父分类筛选
        if (isset($params['parent_id'])) {
            if ($params['parent_id'] === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $params['parent_id']);
            }
        }

        // 排序
        $sortBy = $params['sort_by'] ?? 'sort_order';
        $sortDirection = $params['sort_direction'] ?? 'desc';

        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortDirection);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortDirection);
                break;
            case 'content_count':
                $query->withCount('categorizables')
                      ->orderBy('categorizables_count', $sortDirection);
                break;
            case 'sort_order':
            default:
                $query->orderBy('sort_order', $sortDirection)
                      ->orderBy('name', 'asc');
        }

        $perPage = min($params['per_page'] ?? 20, 100);

        return $query->paginate($perPage);
    }

    /**
     * 获取分类树结构
     *
     * @param bool $onlyActive 只获取激活的分类
     * @return Collection
     */
    public function getCategoryTree(bool $onlyActive = true): Collection
    {
        $query = Category::tree();

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * 获取根分类
     *
     * @param bool $onlyActive 只获取激活的分类
     * @return Collection
     */
    public function getRootCategories(bool $onlyActive = true): Collection
    {
        $query = Category::root()->bySortOrder();

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * 获取子分类
     *
     * @param int $parentId 父分类ID
     * @param bool $onlyActive 只获取激活的分类
     * @return Collection
     */
    public function getChildCategories(int $parentId, bool $onlyActive = true): Collection
    {
        $query = Category::childrenOf($parentId)->bySortOrder();

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * 获取分类的完整路径
     *
     * @param Category $category
     * @return array
     */
    public function getCategoryPath(Category $category): array
    {
        $path = [];
        $current = $category;

        while ($current) {
            array_unshift($path, [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
            ]);
            $current = $current->parent;
        }

        return $path;
    }

    /**
     * 移动分类
     *
     * @param int $categoryId
     * @param int|null $newParentId
     * @return Category
     */
    public function moveCategory(int $categoryId, ?int $newParentId): Category
    {
        $category = Category::findOrFail($categoryId);

        if ($newParentId) {
            $this->validateParentCategory($newParentId, $categoryId);
        }

        $category->update(['parent_id' => $newParentId]);

        return $category;
    }

    /**
     * 批量更新分类排序
     *
     * @param array $sortData [['id' => 1, 'sort_order' => 10], ...]
     * @return bool
     */
    public function batchUpdateSortOrder(array $sortData): bool
    {
        return DB::transaction(function () use ($sortData) {
            foreach ($sortData as $item) {
                Category::where('id', $item['id'])
                       ->update(['sort_order' => $item['sort_order']]);
            }
            return true;
        });
    }

    /**
     * 为内容设置分类
     *
     * @param mixed $categorizable 可分类的模型实例
     * @param array|int|Category $categories 分类
     * @param User|null $user 操作用户
     * @return mixed
     */
    public function setCategories($categorizable, $categories, User $user = null)
    {
        return $categorizable->setCategories($categories, $user);
    }

    /**
     * 为内容添加分类
     *
     * @param mixed $categorizable 可分类的模型实例
     * @param array|int|Category $categories 分类
     * @param User|null $user 操作用户
     * @return mixed
     */
    public function addCategories($categorizable, $categories, User $user = null)
    {
        return $categorizable->addCategories($categories, $user);
    }

    /**
     * 从内容移除分类
     *
     * @param mixed $categorizable 可分类的模型实例
     * @param array|int|Category $categories 要移除的分类，为null时移除所有
     * @return mixed
     */
    public function removeCategories($categorizable, $categories = null)
    {
        return $categorizable->removeCategories($categories);
    }

    /**
     * 获取内容的所有分类
     *
     * @param mixed $categorizable 可分类的模型实例
     * @return Collection
     */
    public function getCategorizableCategories($categorizable): Collection
    {
        return $categorizable->categories;
    }

    /**
     * 通过分类获取内容
     *
     * @param int|string $categoryIdOrSlug 分类ID或slug
     * @param string $categorizableType 内容类型，如 'post', 'article'
     * @param array $params 分页参数
     * @return LengthAwarePaginator
     */
    public function getCategorizedContent($categoryIdOrSlug, string $categorizableType, array $params = []): LengthAwarePaginator
    {
        $category = $this->find($categoryIdOrSlug);

        $method = $this->getCategorizableMethod($categorizableType);
        $query = $category->$method();

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
     * @param string $categorizableType
     * @return string
     */
    protected function getCategorizableMethod(string $categorizableType): string
    {
        return match ($categorizableType) {
            'post' => 'posts',
            'article' => 'articles',
            'topic' => 'topics',
            default => 'categorizables',
        };
    }

    /**
     * 验证父分类是否有效
     *
     * @param int $parentId
     * @param int|null $excludeId 要排除的分类ID（用于更新时避免自引用）
     * @throws \Exception
     */
    protected function validateParentCategory(int $parentId, ?int $excludeId = null): void
    {
        $parent = Category::find($parentId);

        if (!$parent) {
            throw new \Exception('父分类不存在');
        }

        if (!$parent->is_active) {
            throw new \Exception('父分类未激活');
        }

        // 检查是否形成循环引用
        if ($excludeId && $this->wouldCreateCircularReference($parentId, $excludeId)) {
            throw new \Exception('不能将分类设为自己的子分类或形成循环引用');
        }
    }

    /**
     * 检查是否会形成循环引用
     *
     * @param int $parentId
     * @param int $categoryId
     * @return bool
     */
    protected function wouldCreateCircularReference(int $parentId, int $categoryId): bool
    {
        $current = Category::find($parentId);

        while ($current) {
            if ($current->id == $categoryId) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    /**
     * 清理未使用的分类
     *
     * @return int 删除的分类数量
     */
    public function cleanupUnusedCategories(): int
    {
        return Category::whereDoesntHave('categorizables')
                      ->where('is_system', false)
                      ->delete();
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
            default => throw new \InvalidArgumentException('不支持的模型类型: ' . $modelType)
        };
    }
}