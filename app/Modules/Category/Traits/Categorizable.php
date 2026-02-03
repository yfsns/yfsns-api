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

namespace App\Modules\Category\Traits;

use App\Modules\Category\Models\Category;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Categorizable
{
    /**
     * 获取模型的分类
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(
            Category::class,
            'categorizable',
            'categorizables',
            'categorizable_id',
            'category_id'
        )->withTimestamps();
    }

    /**
     * 给模型设置分类
     *
     * @param array|int|Category $categories 分类ID、分类对象或分类ID数组
     * @param User|null $user 设置分类的用户
     * @return $this
     */
    public function setCategories($categories, User $user = null)
    {
        $userId = $user ? $user->id : auth()->id();

        if (!$userId) {
            throw new \Exception('必须指定用户才能设置分类');
        }

        $categoryIds = $this->resolveCategoryIds($categories);

        // 移除所有现有分类关联
        $this->categories()->detach();

        // 添加新的分类关联
        if (!empty($categoryIds)) {
            $this->categories()->attach($categoryIds, ['user_id' => $userId]);
        }

        return $this;
    }

    /**
     * 添加分类到模型
     *
     * @param array|int|Category $categories 分类ID、分类对象或分类ID数组
     * @param User|null $user 添加分类的用户
     * @return $this
     */
    public function addCategories($categories, User $user = null)
    {
        $userId = $user ? $user->id : auth()->id();

        if (!$userId) {
            throw new \Exception('必须指定用户才能添加分类');
        }

        $categoryIds = $this->resolveCategoryIds($categories);

        // 只添加不存在的分类关联
        $existingCategoryIds = $this->categories()->pluck('categories.id')->toArray();
        $newCategoryIds = array_diff($categoryIds, $existingCategoryIds);

        if (!empty($newCategoryIds)) {
            $this->categories()->attach($newCategoryIds, ['user_id' => $userId]);
        }

        return $this;
    }

    /**
     * 从模型移除分类
     *
     * @param array|int|Category $categories 要移除的分类，为null时移除所有分类
     * @return $this
     */
    public function removeCategories($categories = null)
    {
        $categoryIds = $categories ? $this->resolveCategoryIds($categories) : null;

        $this->categories()->detach($categoryIds);

        return $this;
    }

    /**
     * 检查模型是否有指定分类
     *
     * @param int|Category $category 分类ID或分类对象
     * @return bool
     */
    public function hasCategory($category): bool
    {
        $categoryId = $this->resolveCategoryId($category);
        return $this->categories()->where('categories.id', $categoryId)->exists();
    }

    /**
     * 获取模型的主分类（第一个分类）
     *
     * @return Category|null
     */
    public function getPrimaryCategory(): ?Category
    {
        return $this->categories()->first();
    }

    /**
     * 获取模型的分类名称
     *
     * @return array
     */
    public function getCategoryNames(): array
    {
        return $this->categories->pluck('name')->toArray();
    }

    /**
     * 获取模型的分类IDs
     *
     * @return array
     */
    public function getCategoryIds(): array
    {
        return $this->categories->pluck('id')->toArray();
    }

    /**
     * 获取模型的分类层级路径
     *
     * @return array
     */
    public function getCategoryPaths(): array
    {
        return $this->categories->pluck('path')->toArray();
    }

    /**
     * 解析分类为ID数组
     *
     * @param array|int|Category $categories
     * @return array
     */
    protected function resolveCategoryIds($categories): array
    {
        if (is_string($categories)) {
            $categories = [$categories];
        }

        if ($categories instanceof Category) {
            return [$categories->id];
        }

        if (!is_array($categories)) {
            return [];
        }

        $categoryIds = [];
        foreach ($categories as $category) {
            $categoryIds[] = $this->resolveCategoryId($category);
        }

        return array_unique($categoryIds);
    }

    /**
     * 解析单个分类为ID
     *
     * @param int|string|Category $category
     * @return int
     */
    protected function resolveCategoryId($category): int
    {
        if ($category instanceof Category) {
            return $category->id;
        }

        if (is_numeric($category)) {
            return (int) $category;
        }

        if (is_string($category)) {
            // 尝试通过名称或slug查找分类
            $foundCategory = Category::where('name', $category)
                                   ->orWhere('slug', $category)
                                   ->first();

            if (!$foundCategory) {
                // 如果分类不存在，自动创建（顶级分类）
                $foundCategory = Category::create([
                    'name' => $category,
                    'slug' => \Illuminate\Support\Str::slug($category),
                ]);
            }

            return $foundCategory->id;
        }

        throw new \InvalidArgumentException('无效的分类参数');
    }
}