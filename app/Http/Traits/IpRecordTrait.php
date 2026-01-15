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

namespace App\Http\Traits;

use App\Http\Services\IpLocationService;
use Illuminate\Http\Request;

trait IpRecordTrait
{
    /**
     * 获取IP地址信息（用于显示）.
     */
    public function getIpInfoAttribute(): array
    {
        return [
            'ip' => $this->ip,
            'country' => $this->ip_country,
            'region' => $this->ip_region,
            'city' => $this->ip_city,
            'location' => $this->getFormattedLocationAttribute(), // 动态生成
            'user_agent' => $this->user_agent,
        ];
    }

    /**
     * 获取格式化的IP位置信息（动态生成）.
     */
    public function getFormattedLocationAttribute(): string
    {
        if ($this->ip_region && $this->ip_city && 
            $this->ip_region !== '未知' && $this->ip_city !== '未知') {
            return $this->ip_region . '-' . $this->ip_city;
        }

        if ($this->ip_country && $this->ip_country !== '未知') {
            return $this->ip_country;
        }

        return '未知位置';
    }

    /**
     * 记录IP地址信息（优化版：只记录IP，不获取地理位置）.
     */
    protected function recordIpInfo(Request $request, array $data = []): array
    {
        // 获取客户端IP地址（使用 Laravel 原生方法）
        $clientIp = $request->attributes->get('client_ip')
            ?: $request->ip();

        // 只记录IP地址，不获取地理位置信息
        $data['ip'] = $clientIp;
        $data['user_agent'] = $request->userAgent();

        return $data;
    }

    /**
     * 获取IP地址的地理位置信息（单独方法，按需调用）.
     */
    protected function getIpLocation(string $ip): array
    {
        $ipLocationService = app(IpLocationService::class);
        return $ipLocationService->getLocation($ip);
    }

    /**
     * 更新用户的IP地理位置信息（管理员或用户主动调用）.
     */
    protected function updateUserIpLocation(User $user, string $ip): void
    {
        $ipLocation = $this->getIpLocation($ip);

        $user->update([
            'last_login_ip_location' => $ipLocation['region'] ?? '未知',
            'ip_country' => $ipLocation['country'],
            'ip_region' => $ipLocation['region'],
            'ip_city' => $ipLocation['city'],
        ]);
    }
}
