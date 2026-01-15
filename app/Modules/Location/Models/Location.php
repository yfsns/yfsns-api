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

namespace App\Modules\Location\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 位置模型
 * 管理地理位置信息，支持帖子位置关联
 */
class Location extends Model
{
    use HasFactory;

    /**
     * 可批量赋值的属性.
     */
    protected $fillable = [
        'title',
        'latitude',
        'longitude',
        'address',
        'country',
        'province',
        'city',
        'district',
        'place_name',
        'category',
        'post_count',
        'metadata',
    ];

    /**
     * 类型转换.
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'post_count' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * 获取使用该位置的帖子
     */
    public function posts()
    {
        return $this->hasMany(\App\Modules\Post\Models\Post::class);
    }

    /**
     * 增加使用该位置的帖子数量
     */
    public function incrementPostCount(): void
    {
        $this->increment('post_count');
    }

    /**
     * 减少使用该位置的帖子数量
     */
    public function decrementPostCount(): void
    {
        $this->decrement('post_count');
    }

    /**
     * 获取位置的完整地址描述
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->country,
            $this->province,
            $this->city,
            $this->district,
            $this->place_name,
        ]);

        return implode('', $parts) ?: $this->address;
    }

    /**
     * 获取位置的坐标字符串
     */
    public function getCoordinatesAttribute(): string
    {
        return $this->latitude . ',' . $this->longitude;
    }

    /**
     * 作用域：按国家筛选
     */
    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * 作用域：按省份筛选
     */
    public function scopeByProvince($query, string $province)
    {
        return $query->where('province', $province);
    }

    /**
     * 作用域：按城市筛选
     */
    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    /**
     * 作用域：按类别筛选
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * 作用域：按热门程度排序（帖子数量）
     */
    public function scopePopular($query)
    {
        return $query->orderBy('post_count', 'desc');
    }

    /**
     * 查找或创建位置
     */
    public static function findOrCreate(array $locationData): self
    {
        // 根据经纬度查找现有位置（精确匹配）
        $location = static::where('latitude', $locationData['latitude'] ?? 0)
            ->where('longitude', $locationData['longitude'] ?? 0)
            ->first();

        if ($location) {
            return $location;
        }

        // 创建新位置
        return static::create([
            'title' => $locationData['title'] ?? $locationData['place_name'] ?? $locationData['address'] ?? '未知位置',
            'latitude' => $locationData['latitude'] ?? 0,
            'longitude' => $locationData['longitude'] ?? 0,
            'address' => $locationData['address'] ?? '',
            'country' => $locationData['country'] ?? null,
            'province' => $locationData['province'] ?? null,
            'city' => $locationData['city'] ?? null,
            'district' => $locationData['district'] ?? null,
            'place_name' => $locationData['place_name'] ?? null,
            'category' => $locationData['category'] ?? null,
            'post_count' => 0,
            'metadata' => $locationData['metadata'] ?? null,
        ]);
    }
}
