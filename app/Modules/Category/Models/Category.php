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

namespace App\Modules\Category\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'parent_id',
        'sort_order',
        'is_active',
        'is_system',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'metadata' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * 多态关联的类型映射
     */
    protected $morphClassMap = [
        'post' => \App\Modules\Post\Models\Post::class,
        'article' => \App\Modules\Article\Models\Article::class,
        'topic' => \App\Modules\Topic\Models\Topic::class,
    ];

    /**
     * 自动生成slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * 父分类
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * 子分类
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * 所有子分类（递归）
     */
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    /**
     * 获取分类下的所有内容（多态关联）
     */
    public function categorizables(): MorphToMany
    {
        return $this->morphedByMany(
            related: $this->morphClassMap,
            name: 'category',
            table: 'categorizables',
            foreignPivotKey: 'category_id',
            relatedPivotKey: 'categorizable_id',
            relation: 'categorizable_type'
        )->withTimestamps();
    }

    /**
     * 获取特定类型的关联内容
     */
    public function posts()
    {
        return $this->morphedByMany(
            \App\Modules\Post\Models\Post::class,
            'categorizable',
            'categorizables',
            'category_id',
            'categorizable_id'
        );
    }

    public function articles()
    {
        return $this->morphedByMany(
            \App\Modules\Article\Models\Article::class,
            'categorizable',
            'categorizables',
            'category_id',
            'categorizable_id'
        );
    }

    public function topics()
    {
        return $this->morphedByMany(
            \App\Modules\Topic\Models\Topic::class,
            'categorizable',
            'categorizables',
            'category_id',
            'categorizable_id'
        );
    }

    /**
     * 获取分类的完整路径
     */
    public function getPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * 获取分类的层级深度
     */
    public function getDepthAttribute(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    /**
     * 获取分类的URL
     */
    public function getUrlAttribute(): string
    {
        return route('categories.show', $this->slug);
    }

    /**
     * 获取子分类数量
     */
    public function getChildrenCountAttribute(): int
    {
        return $this->children()->count();
    }

    /**
     * 获取内容数量
     */
    public function getContentCountAttribute(): int
    {
        return $this->categorizables()->count();
    }

    /**
     * 作用域：按排序权重排序
     */
    public function scopeBySortOrder($query)
    {
        return $query->orderBy('sort_order', 'desc')->orderBy('name', 'asc');
    }

    /**
     * 作用域：只显示激活的分类
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 作用域：系统分类
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * 作用域：用户分类
     */
    public function scopeUserDefined($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * 作用域：根分类（没有父分类）
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 作用域：获取某个父分类的子分类
     */
    public function scopeChildrenOf($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * 作用域：按层级获取树状结构
     */
    public function scopeTree($query)
    {
        return $query->with(['children' => function ($q) {
            $q->active()->bySortOrder();
        }])->root()->active()->bySortOrder();
    }
}