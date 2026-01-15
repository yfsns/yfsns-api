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

namespace App\Http\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpLocationService
{
    /**
     * IP地址定位API配置.
     */
    protected $apiUrl;

    /**
     * 缓存时间（秒）.
     */
    protected $cacheTime;

    /**
     * 构造函数.
     */
    public function __construct()
    {
        $this->apiUrl = config('ip_location.api_url', 'http://ip-api.com/json/');
        $this->cacheTime = config('ip_location.cache_time', 86400);
    }

    /**
     * 获取IP地址的地理位置信息（简化版，只返回必要字段）.
     */
    public function getLocation(string $ip): array
    {
        // 检查是否为本地IP
        if ($this->isLocalIp($ip)) {
            return [
                'country' => '中国',
                'region' => '本地',
                'city' => '本地',
            ];
        }

        // 尝试从缓存获取
        $cacheKey = "ip_location_{$ip}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $response = Http::timeout(5)->get($this->apiUrl . $ip);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'success') {
                    $location = [
                        'country' => $data['country'] ?? '未知',
                        'region' => $data['regionName'] ?? '未知',
                        'city' => $data['city'] ?? '未知',
                    ];

                    // 缓存结果
                    Cache::put($cacheKey, $location, $this->cacheTime);

                    return $location;
                }
            }
        } catch (Exception $e) {
            Log::warning('IP地址定位失败', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        // 返回默认值
        return [
            'country' => '未知',
            'region' => '未知',
            'city' => '未知',
        ];
    }

    /**
     * 获取客户端真实IP地址
     * 
     * 简化版：直接使用 Laravel 的 ip() 方法
     * TrustProxies 中间件已经处理了代理情况
     */
    public function getClientIp(\Illuminate\Http\Request $request): string
    {
        return $request->ip() ?: '127.0.0.1';
    }

    /**
     * 检查是否为本地IP地址
     */
    protected function isLocalIp(string $ip): bool
    {
        $localRanges = config('ip_location.local_ranges', [
            '127.0.0.0/8',      // 127.0.0.0 - 127.255.255.255
            '10.0.0.0/8',       // 10.0.0.0 - 10.255.255.255
            '172.16.0.0/12',    // 172.16.0.0 - 172.31.255.255
            '192.168.0.0/16',   // 192.168.0.0 - 192.168.255.255
            '::1/128',          // IPv6 localhost
            'fc00::/7',         // IPv6 unique local
            'fe80::/10',        // IPv6 link local
        ]);

        foreach ($localRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查IP是否在指定范围内.
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        [$range, $netmask] = explode('/', $range, 2);
        $rangeDecimal = ip2long($range);
        $ipDecimal = ip2long($ip);
        $wildcardDecimal = 2 ** (32 - $netmask) - 1;
        $netmaskDecimal = ~$wildcardDecimal;

        return ($ipDecimal & $netmaskDecimal) == ($rangeDecimal & $netmaskDecimal);
    }
}
