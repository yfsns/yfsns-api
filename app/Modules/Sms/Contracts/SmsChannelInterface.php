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

namespace App\Modules\Sms\Contracts;

use App\Modules\Sms\Drivers\SmsDriverInterface;

interface SmsChannelInterface extends SmsDriverInterface
{
    /**
     * 获取通道类型标识
     *
     * @return string 通道类型，如 'aliyun', 'tencent', 'twilio' 等
     */
    public function getChannelType(): string;

    /**
     * 获取通道配置表单字段定义
     *
     * @return array 配置字段数组，每个字段包含 type, label, required, default 等信息
     */
    public function getConfigFields(): array;

    /**
     * 验证通道配置
     *
     * @param array $config 配置数据
     * @return array 验证结果 ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public function validateConfig(array $config): array;

    /**
     * 获取通道特性能力
     *
     * @return array 特性数组，如 ['verification', 'notification', 'marketing', 'international']
     */
    public function getCapabilities(): array;

    /**
     * 测试通道连通性
     *
     * @param array $config 配置数据
     * @return array 测试结果 ['success' => bool, 'message' => string, 'data' => mixed]
     */
    public function testConnection(array $config): array;

    /**
     * 获取通道提供商信息
     *
     * @return array 提供商信息 ['name', 'website', 'description', 'regions']
     */
    public function getProviderInfo(): array;

    /**
     * 是否支持国际短信
     *
     * @return bool
     */
    public function supportsInternational(): bool;

    /**
     * 获取支持的国家/地区列表
     *
     * @return array 国家代码数组，如 ['CN', 'US', 'HK']
     */
    public function getSupportedRegions(): array;
}
