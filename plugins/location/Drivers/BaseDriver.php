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

use App\Modules\Location\Contracts\LocationDriverInterface;
use Exception;
use Illuminate\Support\Facades\Http;

/**
 * 定位驱动基类.
 *
 * 提供通用的方法实现
 */
abstract class BaseDriver implements LocationDriverInterface
{
    /**
     * 驱动配置.
     */
    protected array $config = [];

    /**
     * API Key.
     */
    protected string $apiKey = '';

    /**
     * API Secret.
     */
    protected string $apiSecret = '';

    /**
     * 超时时间（秒）.
     */
    protected int $timeout = 5;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        $this->config = $config;
        $this->apiKey = $config['api_key'] ?? '';
        $this->apiSecret = $config['api_secret'] ?? '';
        $this->timeout = $config['timeout'] ?? 5;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * @inheritDoc
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        // 使用Haversine公式计算
        $earthRadius = 6371000; // 地球半径（米）

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * 发送HTTP请求
     */
    protected function httpRequest(string $url, array $params = [], string $method = 'GET'): array
    {
        try {
            if ($method === 'GET') {
                $response = Http::timeout($this->timeout)->get($url, $params);
            } else {
                $response = Http::timeout($this->timeout)->post($url, $params);
            }

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception('HTTP请求失败: ' . $response->status());
        } catch (Exception $e) {
            throw new Exception('HTTP请求异常: ' . $e->getMessage());
        }
    }
}
