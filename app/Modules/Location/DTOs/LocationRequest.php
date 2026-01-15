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

namespace App\Modules\Location\DTOs;

/**
 * 定位请求 DTO.
 */
class LocationRequest
{
    /**
     * 纬度.
     */
    public float $latitude;

    /**
     * 经度.
     */
    public float $longitude;

    /**
     * 坐标系类型
     * - wgs84: GPS坐标
     * - gcj02: 国测局坐标（高德、腾讯）
     * - bd09: 百度坐标.
     */
    public string $coordType = 'gcj02';

    /**
     * 返回数据类型
     * - base: 基础信息
     * - all: 全部信息.
     */
    public string $extensions = 'base';

    /**
     * 额外参数.
     */
    public array $extras = [];

    /**
     * 构造函数.
     */
    public function __construct(float $latitude, float $longitude, string $coordType = 'gcj02')
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->coordType = $coordType;
    }

    /**
     * 设置坐标系类型.
     */
    public function setCoordType(string $coordType): self
    {
        $this->coordType = $coordType;

        return $this;
    }

    /**
     * 设置返回数据类型.
     */
    public function setExtensions(string $extensions): self
    {
        $this->extensions = $extensions;

        return $this;
    }

    /**
     * 设置额外参数.
     */
    public function setExtras(array $extras): self
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * 转换为数组.
     */
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'coord_type' => $this->coordType,
            'extensions' => $this->extensions,
            'extras' => $this->extras,
        ];
    }
}
