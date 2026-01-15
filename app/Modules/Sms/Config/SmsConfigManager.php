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

namespace App\Modules\Sms\Config;

use App\Modules\Sms\Channels\Registry\SmsChannelRegistryInterface;
use App\Modules\Sms\Contracts\SmsChannelInterface;
use App\Modules\Sms\Models\SmsConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmsConfigManager
{
    protected SmsChannelRegistryInterface $channelRegistry;

    public function __construct(SmsChannelRegistryInterface $channelRegistry)
    {
        $this->channelRegistry = $channelRegistry;
    }

    /**
     * 获取默认通道配置
     */
    public function getDefaultChannelConfig(): ?SmsConfig
    {
        return Cache::remember('sms.default_config', 300, function () {
            return SmsConfig::getEnabled();
        });
    }

    /**
     * 获取指定通道类型的配置
     */
    public function getChannelConfig(string $channelType): ?SmsConfig
    {
        return Cache::remember("sms.config.{$channelType}", 300, function () use ($channelType) {
            return SmsConfig::where('driver', $channelType)
                ->where('status', true)
                ->first();
        });
    }

    /**
     * 获取所有启用的通道配置
     */
    public function getAllEnabledConfigs(): array
    {
        return Cache::remember('sms.all_configs', 300, function () {
            return SmsConfig::getAllEnabled()->toArray();
        });
    }

    /**
     * 验证通道配置
     */
    public function validateChannelConfig(string $channelType, array $config): array
    {
        $channel = $this->channelRegistry->getChannel($channelType);
        if (!$channel) {
            return [
                'valid' => false,
                'errors' => ["通道类型 '{$channelType}' 不存在"],
                'warnings' => []
            ];
        }

        return $channel->validateConfig($config);
    }

    /**
     * 保存通道配置
     */
    public function saveChannelConfig(string $channelType, array $config, string $name = null): SmsConfig
    {
        // 验证配置
        $validation = $this->validateChannelConfig($channelType, $config);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException('配置验证失败: ' . implode(', ', $validation['errors']));
        }

        // 查找现有配置或创建新配置
        $smsConfig = SmsConfig::where('driver', $channelType)->first();

        if (!$smsConfig) {
            $smsConfig = new SmsConfig();
            $smsConfig->driver = $channelType;
        }

        $smsConfig->name = $name ?: $this->getChannelDisplayName($channelType);
        $smsConfig->config = $config;
        $smsConfig->status = true; // 启用配置
        $smsConfig->save();

        // 清除缓存
        $this->clearConfigCache();

        Log::info("短信通道配置已保存", [
            'channel_type' => $channelType,
            'config_id' => $smsConfig->id
        ]);

        return $smsConfig;
    }

    /**
     * 禁用通道配置
     */
    public function disableChannelConfig(string $channelType): bool
    {
        $config = SmsConfig::where('driver', $channelType)->first();
        if ($config) {
            $config->status = false;
            $config->save();

            $this->clearConfigCache();

            Log::info("短信通道配置已禁用", ['channel_type' => $channelType]);
            return true;
        }

        return false;
    }

    /**
     * 删除通道配置
     */
    public function deleteChannelConfig(string $channelType): bool
    {
        $deleted = SmsConfig::where('driver', $channelType)->delete();

        if ($deleted > 0) {
            $this->clearConfigCache();
            Log::info("短信通道配置已删除", ['channel_type' => $channelType]);
        }

        return $deleted > 0;
    }

    /**
     * 测试通道配置连通性
     */
    public function testChannelConfig(string $channelType, array $config): array
    {
        $channel = $this->channelRegistry->getChannel($channelType);
        if (!$channel) {
            return [
                'success' => false,
                'message' => "通道类型 '{$channelType}' 不存在"
            ];
        }

        try {
            return $channel->testConnection($config);
        } catch (\Exception $e) {
            Log::error("通道连通性测试失败", [
                'channel_type' => $channelType,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => '测试失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取通道显示名称
     */
    protected function getChannelDisplayName(string $channelType): string
    {
        $channel = $this->channelRegistry->getChannel($channelType);
        return $channel ? $channel->getName() : ucfirst($channelType) . ' 短信';
    }

    /**
     * 清除配置缓存
     */
    public function clearConfigCache(): void
    {
        Cache::forget('sms.default_config');
        Cache::forget('sms.all_configs');

        // 清除所有通道配置缓存
        $channels = $this->channelRegistry->getAvailableChannels();
        foreach (array_keys($channels) as $channelType) {
            Cache::forget("sms.config.{$channelType}");
        }

        Log::debug("短信配置缓存已清除");
    }

    /**
     * 获取通道配置状态
     */
    public function getChannelStatus(string $channelType): array
    {
        $config = $this->getChannelConfig($channelType);
        $channel = $this->channelRegistry->getChannel($channelType);

        return [
            'channel_type' => $channelType,
            'configured' => $config !== null,
            'enabled' => $config && $config->status,
            'available' => $channel !== null,
            'name' => $config ? $config->name : ($channel ? $channel->getName() : '未知'),
            'capabilities' => $channel ? $channel->getCapabilities() : [],
            'last_updated' => $config ? $config->updated_at?->toISOString() : null,
        ];
    }

    /**
     * 获取所有通道状态
     */
    public function getAllChannelStatuses(): array
    {
        $statuses = [];
        $channels = $this->channelRegistry->getAvailableChannels();

        foreach ($channels as $channelType => $driverClass) {
            $statuses[$channelType] = $this->getChannelStatus($channelType);
        }

        return $statuses;
    }
}
