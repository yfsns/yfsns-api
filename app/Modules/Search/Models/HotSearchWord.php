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

namespace App\Modules\Search\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotSearchWord extends Model
{
    protected $table = 'search_hot_words';

    protected $fillable = [
        'keyword',
        'search_count',
        'click_count',
        'weight',
        'is_active',
        'created_by',
        'updated_by',
        'last_searched_at',
    ];

    protected $casts = [
        'search_count' => 'integer',
        'click_count' => 'integer',
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
        'last_searched_at' => 'datetime',
    ];

    /**
     * 用户关系.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * 增加搜索次数.
     */
    public function incrementSearchCount(): void
    {
        $this->increment('search_count');
        $this->update(['last_searched_at' => now()]);
    }

    /**
     * 增加点击次数.
     */
    public function incrementClickCount(): void
    {
        $this->increment('click_count');
    }

    /**
     * 获取热门搜索词列表.
     */
    public static function getActiveHotWords(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->orderBy('weight', 'desc')
            ->orderBy('search_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 根据关键词查找或创建.
     */
    public static function findOrCreateByKeyword(string $keyword): self
    {
        return self::firstOrCreate(
            ['keyword' => $keyword],
            [
                'search_count' => 0,
                'click_count' => 0,
                'weight' => 1.00,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]
        );
    }

    /**
     * 批量更新权重.
     */
    public static function updateWeights(array $weights): void
    {
        foreach ($weights as $keyword => $weight) {
            self::where('keyword', $keyword)->update(['weight' => $weight]);
        }
    }
}
