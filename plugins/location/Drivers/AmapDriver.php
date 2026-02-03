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

namespace Plugins\Location\Drivers;

use App\Modules\Location\DTOs\LocationRequest;
use App\Modules\Location\DTOs\LocationResponse;
use Exception;

/**
 * 高德地图定位驱动.
 *
 * API文档: https://lbs.amap.com/api/webservice/guide/api/georegeo
 */
class AmapDriver extends BaseDriver
{
    /**
     * API基础URL.
     */
    protected string $baseUrl = 'https://restapi.amap.com/v3';

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'amap';
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * @inheritDoc
     */
    public function reverseGeocode(LocationRequest $request): LocationResponse
    {
        try {
            $url = $this->baseUrl . '/geocode/regeo';
            $params = [
                'key' => $this->apiKey,
                'location' => $request->longitude . ',' . $request->latitude,
                'extensions' => 'all',
                'output' => 'JSON',
            ];

            $data = $this->httpRequest($url, $params);

            if (!isset($data['status']) || $data['status'] !== '1') {
                return LocationResponse::fail($data['info'] ?? '高德地图API调用失败');
            }

            $response = LocationResponse::success()
                ->setDriver($this->getName())
                ->setCoordinate($request->latitude, $request->longitude);

            if (isset($data['regeocode'])) {
                $regeocode = $data['regeocode'];

                // 设置格式化地址
                if (isset($regeocode['formatted_address'])) {
                    $response->setFormattedAddress($regeocode['formatted_address']);
                }

                // 设置地址信息
                if (isset($regeocode['addressComponent'])) {
                    $component = $regeocode['addressComponent'];

                    $response->setAddress(
                        $component['country'] ?? null,
                        $component['province'] ?? null,
                        $component['city'] ?? null,
                        $component['district'] ?? null,
                        $component['streetNumber']['street'] ?? null
                    );

                    // 设置行政区划代码
                    if (isset($component['adcode'])) {
                        $response->adcode = $component['adcode'];
                    }
                }

                // 设置兴趣点信息
                if (isset($regeocode['pois']) && is_array($regeocode['pois'])) {
                    $response->pois = array_map(function ($poi) {
                        return [
                            'id' => $poi['id'] ?? null,
                            'name' => $poi['name'] ?? null,
                            'address' => $poi['address'] ?? null,
                            'location' => $poi['location'] ?? null,
                            'distance' => $poi['distance'] ?? null,
                        ];
                    }, $regeocode['pois']);
                }
            }

            $response->setRawData($data);
            return $response;

        } catch (Exception $e) {
            return LocationResponse::fail('高德地图逆地理编码失败: ' . $e->getMessage())
                ->setDriver($this->getName());
        }
    }

    /**
     * @inheritDoc
     */
    public function geocode(string $address, ?string $city = null): LocationResponse
    {
        try {
            $url = $this->baseUrl . '/geocode/geo';
            $params = [
                'key' => $this->apiKey,
                'address' => $address,
                'output' => 'JSON',
            ];

            if ($city) {
                $params['city'] = $city;
            }

            $data = $this->httpRequest($url, $params);

            if (!isset($data['status']) || $data['status'] !== '1') {
                return LocationResponse::fail($data['info'] ?? '高德地图API调用失败');
            }

            $response = LocationResponse::success()
                ->setDriver($this->getName());

            if (isset($data['geocodes']) && is_array($data['geocodes']) && count($data['geocodes']) > 0) {
                $geocode = $data['geocodes'][0];

                // 设置格式化地址
                if (isset($geocode['formatted_address'])) {
                    $response->setFormattedAddress($geocode['formatted_address']);
                }

                // 解析坐标
                if (isset($geocode['location'])) {
                    [$lng, $lat] = explode(',', $geocode['location']);
                    $response->setCoordinate((float)$lat, (float)$lng);
                }

                // 设置地址信息
                if (isset($geocode['addressComponent'])) {
                    $component = $geocode['addressComponent'];

                    $response->setAddress(
                        $component['country'] ?? null,
                        $component['province'] ?? null,
                        $component['city'] ?? null,
                        $component['district'] ?? null,
                        $component['streetNumber']['street'] ?? null
                    );

                    // 设置行政区划代码
                    if (isset($component['adcode'])) {
                        $response->adcode = $component['adcode'];
                    }
                }
            }

            $response->setRawData($data);
            return $response;

        } catch (Exception $e) {
            return LocationResponse::fail('高德地图地理编码失败: ' . $e->getMessage())
                ->setDriver($this->getName());
        }
    }

    /**
     * @inheritDoc
     */
    public function getLocationByIp(string $ip): LocationResponse
    {
        try {
            $url = $this->baseUrl . '/ip';
            $params = [
                'key' => $this->apiKey,
                'ip' => $ip,
                'output' => 'JSON',
            ];

            $data = $this->httpRequest($url, $params);

            if (!isset($data['status']) || $data['status'] !== '1') {
                return LocationResponse::fail($data['info'] ?? '高德地图API调用失败');
            }

            $response = LocationResponse::success()
                ->setDriver($this->getName());

            if (isset($data['rectangle'])) {
                // 解析IP定位结果
                $response->setRawData($data);
            }

            return $response;

        } catch (Exception $e) {
            return LocationResponse::fail('高德地图IP定位失败: ' . $e->getMessage())
                ->setDriver($this->getName());
        }
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}
