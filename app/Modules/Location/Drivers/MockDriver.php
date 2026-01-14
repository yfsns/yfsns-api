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

namespace App\Modules\Location\Drivers;

use App\Modules\Location\DTOs\LocationRequest;
use App\Modules\Location\DTOs\LocationResponse;

/**
 * 模拟定位驱动.
 *
 * 用于测试和演示，不调用真实API
 */
class MockDriver extends BaseDriver
{
    public function getName(): string
    {
        return 'mock';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function isAvailable(): bool
    {
        return true; // 总是可用
    }

    public function reverseGeocode(LocationRequest $request): LocationResponse
    {
        // 模拟返回数据
        return LocationResponse::success()
            ->setDriver($this->getName())
            ->setCoordinate($request->latitude, $request->longitude)
            ->setAddress('中国', '北京市', '北京市', '朝阳区', '望京街道')
            ->setFormattedAddress('北京市朝阳区阜通东大街6号望京SOHO塔1 A座')
            ->setRawData([
                'mock' => true,
                'message' => '这是模拟数据，用于测试',
            ]);
    }

    public function geocode(string $address, ?string $city = null): LocationResponse
    {
        // 模拟返回坐标
        return LocationResponse::success()
            ->setDriver($this->getName())
            ->setCoordinate(39.908823, 116.397470)
            ->setFormattedAddress($address)
            ->setRawData([
                'mock' => true,
                'message' => '这是模拟数据，用于测试',
            ]);
    }

    public function getLocationByIp(string $ip): LocationResponse
    {
        // 模拟返回IP定位
        $response = LocationResponse::success()
            ->setDriver($this->getName())
            ->setCoordinate(39.904989, 116.405285)
            ->setRawData([
                'mock' => true,
                'message' => '这是模拟数据，用于测试',
                'ip' => $ip,
            ]);

        $response->province = '北京市';
        $response->city = '北京市';
        $response->district = '朝阳区';

        return $response;
    }
}
