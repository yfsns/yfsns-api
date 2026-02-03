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
 * 百度地图定位驱动.
 *
 * API文档: https://lbsyun.baidu.com/index.php?title=webapi
 */
class BaiduDriver extends BaseDriver
{
    /**
     * API基础URL.
     */
    protected string $baseUrl = 'https://api.map.baidu.com';

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'baidu';
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
            $url = $this->baseUrl . '/reverse_geocoding/v3';
            $params = [
                'ak' => $this->apiKey,
                'location' => $request->latitude . ',' . $request->longitude,
                'output' => 'json',
                'coordtype' => 'bd09ll', // 百度坐标系
            ];

            $data = $this->httpRequest($url, $params);

            if (!isset($data['status']) || $data['status'] !== 0) {
                $message = $data['message'] ?? '百度地图API调用失败';
                return LocationResponse::fail($message);
            }

            $response = LocationResponse::success()
                ->setDriver($this->getName())
                ->setCoordinate($request->latitude, $request->longitude);

            if (isset($data['result'])) {
                $result = $data['result'];

                // 设置格式化地址
                if (isset($result['formatted_address'])) {
                    $response->setFormattedAddress($result['formatted_address']);
                }

                // 设置地址信息
                if (isset($result['addressComponent'])) {
                    $component = $result['addressComponent'];

                    $response->setAddress(
                        $component['country'] ?? null,
                        $component['province'] ?? null,
                        $component['city'] ?? null,
                        $component['district'] ?? null,
                        $component['street'] ?? null
                    );

                    // 设置行政区划代码
                    if (isset($component['adcode'])) {
                        $response->adcode = $component['adcode'];
                    }
                }

                // 设置兴趣点信息
                if (isset($result['pois']) && is_array($result['pois'])) {
                    $response->pois = array_map(function ($poi) {
                        return [
                            'id' => $poi['id'] ?? null,
                            'name' => $poi['name'] ?? null,
                            'address' => $poi['addr'] ?? null,
                            'location' => $poi['point']['x'] . ',' . $poi['point']['y'] ?? null,
                            'distance' => $poi['distance'] ?? null,
                        ];
                    }, $result['pois']);
                }
            }

            $response->setRawData($data);
            return $response;

        } catch (Exception $e) {
            return LocationResponse::fail('百度地图逆地理编码失败: ' . $e->getMessage())
                ->setDriver($this->getName());
        }
    }

    /**
     * @inheritDoc
     */
    public function geocode(string $address, ?string $city = null): LocationResponse
    {
        try {
            $url = $this->baseUrl . '/geocoding/v3';
            $params = [
                'ak' => $this->apiKey,
                'address' => $address,
                'output' => 'json',
            ];

            if ($city) {
                $params['city'] = $city;
            }

            $data = $this->httpRequest($url, $params);

            if (!isset($data['status']) || $data['status'] !== 0) {
                $message = $data['message'] ?? '百度地图API调用失败';
                return LocationResponse::fail($message);
            }

            $response = LocationResponse::success()
                ->setDriver($this->getName());

            if (isset($data['result'])) {
                $result = $data['result'];

                // 设置坐标
                if (isset($result['location'])) {
                    $location = $result['location'];
                    $response->setCoordinate($location['lat'], $location['lng']);
                }

                // 设置精度
                if (isset($result['precise'])) {
                    $response->address = $result['precise'] ? 'precise' : 'vague';
                }

                // 设置置信度
                if (isset($result['confidence'])) {
                    $response->postcode = (string)$result['confidence'];
                }

                // 设置地址信息（解析level）
                if (isset($result['level'])) {
                    // 百度地图返回的level信息可以用来判断地址类型
                    $response->district = $result['level'];
                }
            }

            $response->setRawData($data);
            return $response;

        } catch (Exception $e) {
            return LocationResponse::fail('百度地图地理编码失败: ' . $e->getMessage())
                ->setDriver($this->getName());
        }
    }

    /**
     * @inheritDoc
     */
    public function getLocationByIp(string $ip): LocationResponse
    {
        try {
            $url = $this->baseUrl . '/location/ip';
            $params = [
                'ak' => $this->apiKey,
                'ip' => $ip,
                'coor' => 'bd09ll', // 返回百度坐标
            ];

            $data = $this->httpRequest($url, $params);

            if (!isset($data['status']) || $data['status'] !== '0') {
                $message = $data['message'] ?? '百度地图API调用失败';
                return LocationResponse::fail($message);
            }

            $response = LocationResponse::success()
                ->setDriver($this->getName());

            if (isset($data['content'])) {
                $content = $data['content'];

                // 设置坐标
                if (isset($content['point'])) {
                    $point = $content['point'];
                    $response->setCoordinate($point['y'], $point['x']);
                }

                // 设置地址信息
                if (isset($content['address_detail'])) {
                    $detail = $content['address_detail'];

                    $response->setAddress(
                        $detail['country'] ?? null,
                        $detail['province'] ?? null,
                        $detail['city'] ?? null,
                        $detail['district'] ?? null,
                        $detail['street'] ?? null
                    );
                }

                // 设置格式化地址
                if (isset($content['address'])) {
                    $response->setFormattedAddress($content['address']);
                }
            }

            $response->setRawData($data);
            return $response;

        } catch (Exception $e) {
            return LocationResponse::fail('百度地图IP定位失败: ' . $e->getMessage())
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
