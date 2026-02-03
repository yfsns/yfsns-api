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

namespace App\Modules\Tag\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'usage_count',
        'sort_order',
        'is_system',
        'metadata',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'metadata' => 'array',
        'usage_count' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * 多态关联的类型映射
     */
    protected $morphClassMap = [
        'post' => \App\Modules\Post\Models\Post::class,
        'comment' => \App\Modules\Comment\Models\Comment::class,
        'topic' => \App\Modules\Topic\Models\Topic::class,
        'article' => \App\Modules\Article\Models\Article::class,
        'user' => \App\Modules\User\Models\User::class,
    ];

    /**
     * 自动生成slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name') && empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * 获取标签下的所有内容（多态关联）
     */
    public function taggables(): MorphToMany
    {
        return $this->morphedByMany(
            related: $this->morphClassMap,
            name: 'tag',
            table: 'taggables',
            foreignPivotKey: 'tag_id',
            relatedPivotKey: 'taggable_id',
            relation: 'taggable_type'
        )->withTimestamps();
    }

    /**
     * 获取特定类型的关联内容
     */
    public function posts()
    {
        return $this->morphedByMany(
            \App\Modules\Post\Models\Post::class,
            'taggable',
            'taggables',
            'tag_id',
            'taggable_id'
        );
    }

    public function articles()
    {
        return $this->morphedByMany(
            \App\Modules\Article\Models\Article::class,
            'taggable',
            'taggables',
            'tag_id',
            'taggable_id'
        );
    }

    public function topics()
    {
        return $this->morphedByMany(
            \App\Modules\Topic\Models\Topic::class,
            'taggable',
            'taggables',
            'tag_id',
            'taggable_id'
        );
    }

    /**
     * 增加使用次数
     */
    public function incrementUsageCount(): void
    {
        $this->increment('usage_count');
    }

    /**
     * 减少使用次数
     */
    public function decrementUsageCount(): void
    {
        $this->decrement('usage_count');
    }

    /**
     * 获取标签的显示名称
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * 获取标签的URL
     */
    public function getUrlAttribute(): string
    {
        return route('tags.show', $this->slug);
    }

    /**
     * 作用域：按使用次数排序
     */
    public function scopeByUsageCount($query)
    {
        return $query->orderBy('usage_count', 'desc');
    }

    /**
     * 作用域：按排序权重排序
     */
    public function scopeBySortOrder($query)
    {
        return $query->orderBy('sort_order', 'desc')->orderBy('usage_count', 'desc');
    }

    /**
     * 作用域：系统标签
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * 作用域：用户标签
     */
    public function scopeUserDefined($query)
    {
        return $query->where('is_system', false);
    }
}