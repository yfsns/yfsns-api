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

namespace App\Modules\Location\Services;

use App\Modules\Location\Models\Location;

/**
 * 位置服务
 * 提供位置相关的业务逻辑
 */
class LocationService
{
    /**
     * 获取热门位置
     *
     * @param int $limit 限制数量
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPopularLocations(int $limit = 20)
    {
        return Location::popular()
            ->withCount('posts')
            ->take($limit)
            ->get();
    }

    /**
     * 按城市获取位置
     *
     * @param string $city 城市名
     * @param int $limit 限制数量
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLocationsByCity(string $city, int $limit = 50)
    {
        return Location::byCity($city)
            ->popular()
            ->take($limit)
            ->get();
    }

    /**
     * 按省份获取位置
     *
     * @param string $province 省份名
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLocationsByProvince(string $province)
    {
        return Location::byProvince($province)
            ->popular()
            ->get();
    }

    /**
     * 按类别获取位置
     *
     * @param string $category 类别
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLocationsByCategory(string $category)
    {
        return Location::byCategory($category)
            ->popular()
            ->get();
    }

    /**
     * 查找附近的热门位置
     *
     * @param float $latitude 纬度
     * @param float $longitude 经度
     * @param float $radiusKm 搜索半径（公里）
     * @param int $limit 限制数量
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNearbyPopularLocations(float $latitude, float $longitude, float $radiusKm = 5.0, int $limit = 20)
    {
        return Location::selectRaw("
                *,
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
            ", [$latitude, $longitude, $latitude])
            ->having('distance', '<', $radiusKm)
            ->orderBy('post_count', 'desc')
            ->orderBy('distance', 'asc')
            ->take($limit)
            ->get();
    }

    /**
     * 更新位置的帖子计数
     *
     * @param int $locationId 位置ID
     * @return void
     */
    public function updatePostCount(int $locationId): void
    {
        $count = Location::find($locationId)?->posts()->count() ?? 0;

        Location::where('id', $locationId)->update(['post_count' => $count]);
    }

    /**
     * 清理没有帖子使用的位置
     *
     * @return int 删除的数量
     */
    public function cleanupUnusedLocations(): int
    {
        return Location::where('post_count', 0)->delete();
    }

    /**
     * 获取位置统计信息
     *
     * @return array
     */
    public function getLocationStats(): array
    {
        return [
            'total_locations' => Location::count(),
            'total_posts_with_location' => Location::sum('post_count'),
            'popular_cities' => Location::select('city')
                ->selectRaw('SUM(post_count) as total_posts')
                ->whereNotNull('city')
                ->groupBy('city')
                ->orderBy('total_posts', 'desc')
                ->take(10)
                ->get(),
            'popular_categories' => Location::select('category')
                ->selectRaw('SUM(post_count) as total_posts')
                ->whereNotNull('category')
                ->groupBy('category')
                ->orderBy('total_posts', 'desc')
                ->take(10)
                ->get(),
        ];
    }
}