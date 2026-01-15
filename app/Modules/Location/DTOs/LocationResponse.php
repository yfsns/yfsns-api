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
 * 定位响应 DTO.
 */
class LocationResponse
{
    /**
     * 是否成功
     */
    public bool $success = false;

    /**
     * 国家.
     */
    public ?string $country = null;

    /**
     * 省份.
     */
    public ?string $province = null;

    /**
     * 城市
     */
    public ?string $city = null;

    /**
     * 区县
     */
    public ?string $district = null;

    /**
     * 街道.
     */
    public ?string $street = null;

    /**
     * 地点名称
     */
    public ?string $title = null;

    /**
     * 详细地址
     */
    public ?string $address = null;

    /**
     * 格式化地址
     */
    public ?string $formattedAddress = null;

    /**
     * 纬度.
     */
    public ?float $latitude = null;

    /**
     * 经度.
     */
    public ?float $longitude = null;

    /**
     * 行政区划代码
     */
    public ?string $adcode = null;

    /**
     * 邮政编码
     */
    public ?string $postcode = null;

    /**
     * POI 信息（兴趣点）.
     */
    public array $pois = [];

    /**
     * 原始响应数据.
     */
    public array $rawData = [];

    /**
     * 错误信息.
     */
    public ?string $error = null;

    /**
     * 驱动名称.
     */
    public string $driver = 'unknown';

    /**
     * 创建成功响应.
     */
    public static function success(): self
    {
        $response = new self();
        $response->success = true;

        return $response;
    }

    /**
     * 创建失败响应.
     */
    public static function fail(string $error): self
    {
        $response = new self();
        $response->success = false;
        $response->error = $error;

        return $response;
    }

    /**
     * 设置驱动名称.
     */
    public function setDriver(string $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * 设置坐标.
     */
    public function setCoordinate(float $latitude, float $longitude): self
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * 设置地址信息.
     */
    public function setAddress(
        ?string $country = null,
        ?string $province = null,
        ?string $city = null,
        ?string $district = null,
        ?string $street = null
    ): self {
        $this->country = $country;
        $this->province = $province;
        $this->city = $city;
        $this->district = $district;
        $this->street = $street;

        return $this;
    }

    /**
     * 设置地点名称
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * 设置格式化地址
     */
    public function setFormattedAddress(string $address): self
    {
        $this->formattedAddress = $address;

        return $this;
    }

    /**
     * 设置原始数据.
     */
    public function setRawData(array $data): self
    {
        $this->rawData = $data;

        return $this;
    }

    /**
     * 转换为数组.
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'driver' => $this->driver,
            'title' => $this->title,
            'country' => $this->country,
            'province' => $this->province,
            'city' => $this->city,
            'district' => $this->district,
            'street' => $this->street,
            'address' => $this->address,
            'formattedAddress' => $this->formattedAddress, // 改为驼峰格式
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'adcode' => $this->adcode,
            'postcode' => $this->postcode,
            'pois' => $this->pois,
            'error' => $this->error,
        ];
    }
}
