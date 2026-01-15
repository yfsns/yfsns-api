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
use Exception;

/**
 * 腾讯地图定位驱动.
 *
 * API文档: https://lbs.qq.com/service/webService/webServiceGuide/webServiceGcoder
 */
class TencentDriver extends BaseDriver
{
    /**
     * API基础URL.
     */
    protected string $baseUrl = 'https://apis.map.qq.com/ws';

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'tencent';
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string
    {
        return '1.0';
    }

    /**
     * @inheritDoc
     *
     * 逆地理编码（坐标转地址）
     * 文档: https://lbs.qq.com/service/webService/webServiceGuide/webServiceGcoder
     */
    public function reverseGeocode(LocationRequest $request): LocationResponse
    {
        $url = $this->baseUrl . '/geocoder/v1/';

        $params = [
            'key' => $this->apiKey,
            'location' => $request->latitude . ',' . $request->longitude,
            'output' => 'json',
            'get_poi' => 1, // 返回周边POI列表
        ];

        try {
            $data = $this->httpRequestWithReferer($url, $params);

            if ($data['status'] !== 0) {
                return LocationResponse::fail($data['message'] ?? '请求失败')
                    ->setDriver($this->getName());
            }

            $result = $data['result'] ?? [];
            $addressComponent = $result['address_component'] ?? [];
            $adInfo = $result['ad_info'] ?? [];

            $response = LocationResponse::success()
                ->setDriver($this->getName())
                ->setCoordinate($request->latitude, $request->longitude)
                ->setAddress(
                    $addressComponent['nation'] ?? '中国',
                    $addressComponent['province'] ?? null,
                    $addressComponent['city'] ?? null,
                    $addressComponent['district'] ?? null,
                    $addressComponent['street'] ?? null
                )
                ->setFormattedAddress($result['address'] ?? '')
                ->setRawData($data);

            $response->adcode = $adInfo['adcode'] ?? null;
            $response->pois = $result['pois'] ?? [];

            return $response;
        } catch (Exception $e) {
            return LocationResponse::fail($e->getMessage())
                ->setDriver($this->getName());
        }
    }

    /**
     * @inheritDoc
     *
     * 地理编码（地址转坐标）
     */
    public function geocode(string $address, ?string $city = null): LocationResponse
    {
        $url = $this->baseUrl . '/geocoder/v1/';

        $params = [
            'key' => $this->apiKey,
            'address' => $address,
            'output' => 'json',
        ];

        if ($city) {
            $params['region'] = $city;
        }

        try {
            $data = $this->httpRequestWithReferer($url, $params);

            if ($data['status'] !== 0) {
                return LocationResponse::fail($data['message'] ?? '请求失败')
                    ->setDriver($this->getName());
            }

            $result = $data['result'] ?? [];
            $location = $result['location'] ?? [];

            if (empty($location)) {
                return LocationResponse::fail('未找到地址')
                    ->setDriver($this->getName());
            }

            $response = LocationResponse::success()
                ->setDriver($this->getName())
                ->setCoordinate((float) $location['lat'], (float) $location['lng'])
                ->setFormattedAddress($result['title'] ?? $address)
                ->setRawData($data);

            $adInfo = $result['ad_info'] ?? [];
            $response->adcode = $adInfo['adcode'] ?? null;
            $response->province = $adInfo['province'] ?? null;
            $response->city = $adInfo['city'] ?? null;
            $response->district = $adInfo['district'] ?? null;

            return $response;
        } catch (Exception $e) {
            return LocationResponse::fail($e->getMessage())
                ->setDriver($this->getName());
        }
    }

    /**
     * @inheritDoc
     *
     * IP定位
     * 文档: https://lbs.qq.com/service/webService/webServiceGuide/webServiceIp
     */
    public function getLocationByIp(string $ip): LocationResponse
    {
        $url = $this->baseUrl . '/location/v1/ip';

        $params = [
            'key' => $this->apiKey,
            'ip' => $ip,
            'output' => 'json',
        ];

        try {
            $data = $this->httpRequestWithReferer($url, $params);

            if ($data['status'] !== 0) {
                return LocationResponse::fail($data['message'] ?? '请求失败')
                    ->setDriver($this->getName());
            }

            $result = $data['result'] ?? [];
            $adInfo = $result['ad_info'] ?? [];
            $location = $result['location'] ?? [];

            $response = LocationResponse::success()
                ->setDriver($this->getName())
                ->setRawData($data);

            // 设置坐标
            if (! empty($location)) {
                $response->setCoordinate((float) $location['lat'], (float) $location['lng']);
            }

            // 设置地址信息
            $response->province = $adInfo['province'] ?? null;
            $response->city = $adInfo['city'] ?? null;
            $response->district = $adInfo['district'] ?? null;
            $response->adcode = $adInfo['adcode'] ?? null;

            return $response;
        } catch (Exception $e) {
            return LocationResponse::fail($e->getMessage())
                ->setDriver($this->getName());
        }
    }

    /**
     * 发送带 Referer 的 HTTP 请求
     *
     * 腾讯地图 WebService API 需要 Referer 头
     */
    protected function httpRequestWithReferer(string $url, array $params = []): array
    {
        try {
            $referer = config('app.url', 'http://localhost');

            $response = \Illuminate\Support\Facades\Http::timeout($this->timeout)
                ->withHeaders([
                    'Referer' => $referer,
                ])
                ->get($url, $params);

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('HTTP请求失败: ' . $response->status());
        } catch (Exception $e) {
            throw new Exception('HTTP请求异常: ' . $e->getMessage());
        }
    }
}
