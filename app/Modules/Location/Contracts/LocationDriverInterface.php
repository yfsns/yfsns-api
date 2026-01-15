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

namespace App\Modules\Location\Contracts;

use App\Modules\Location\DTOs\LocationRequest;
use App\Modules\Location\DTOs\LocationResponse;

/**
 * 定位服务驱动接口.
 *
 * 所有定位服务提供商（高德、百度、腾讯等）必须实现此接口
 */
interface LocationDriverInterface
{
    /**
     * 获取驱动名称.
     */
    public function getName(): string;

    /**
     * 获取驱动版本.
     */
    public function getVersion(): string;

    /**
     * 初始化驱动配置.
     */
    public function initialize(array $config): void;

    /**
     * 根据坐标获取地址信息（逆地理编码）.
     */
    public function reverseGeocode(LocationRequest $request): LocationResponse;

    /**
     * 根据地址获取坐标信息（地理编码）.
     */
    public function geocode(string $address, ?string $city = null): LocationResponse;

    /**
     * 根据IP地址获取位置信息.
     */
    public function getLocationByIp(string $ip): LocationResponse;

    /**
     * 计算两点之间的距离（单位：米）.
     *
     * @param float $lat1 纬度1
     * @param float $lng1 经度1
     * @param float $lat2 纬度2
     * @param float $lng2 经度2
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float;

    /**
     * 检查驱动是否可用.
     */
    public function isAvailable(): bool;

    /**
     * 获取驱动配置信息.
     */
    public function getConfig(): array;
}
