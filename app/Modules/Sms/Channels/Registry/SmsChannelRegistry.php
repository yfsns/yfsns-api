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

namespace App\Modules\Sms\Channels\Registry;

use App\Modules\Sms\Contracts\SmsChannelInterface;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsChannelRegistry implements SmsChannelRegistryInterface
{
    /**
     * 注册的通道映射 [channelType => driverClass]
     */
    protected array $channels = [];

    /**
     * 通道实例缓存
     */
    protected array $channelInstances = [];

    /**
     * 注册短信通道
     */
    public function registerChannel(string $channelType, string $driverClass): void
    {
        if (!class_exists($driverClass)) {
            throw new RuntimeException("通道驱动类不存在: {$driverClass}");
        }

        if (!is_subclass_of($driverClass, SmsChannelInterface::class)) {
            throw new RuntimeException("通道驱动类必须实现SmsChannelInterface: {$driverClass}");
        }

        $this->channels[$channelType] = $driverClass;

        // 清除实例缓存，确保使用新的驱动类
        unset($this->channelInstances[$channelType]);

        Log::info("短信通道已注册", [
            'channel_type' => $channelType,
            'driver_class' => $driverClass
        ]);
    }

    /**
     * 获取短信通道实例
     */
    public function getChannel(string $channelType): ?SmsChannelInterface
    {
        if (!isset($this->channels[$channelType])) {
            return null;
        }

        // 使用实例缓存，避免重复创建
        if (!isset($this->channelInstances[$channelType])) {
            try {
                $driverClass = $this->channels[$channelType];
                $this->channelInstances[$channelType] = app($driverClass);
            } catch (\Exception $e) {
                Log::error("创建通道实例失败", [
                    'channel_type' => $channelType,
                    'driver_class' => $this->channels[$channelType],
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }

        return $this->channelInstances[$channelType];
    }

    /**
     * 检查通道是否存在
     */
    public function hasChannel(string $channelType): bool
    {
        return isset($this->channels[$channelType]);
    }

    /**
     * 获取所有可用通道列表
     */
    public function getAvailableChannels(): array
    {
        return $this->channels;
    }

    /**
     * 获取通道信息列表（包含元数据）
     */
    public function getChannelInfos(): array
    {
        $infos = [];

        foreach ($this->channels as $channelType => $driverClass) {
            try {
                $channel = $this->getChannel($channelType);
                if ($channel) {
                    $infos[$channelType] = [
                        'type' => $channelType,
                        'name' => $channel->getName(),
                        'driver_class' => $driverClass,
                        'capabilities' => $channel->getCapabilities(),
                        'config_fields' => $channel->getConfigFields(),
                        'is_builtin' => $this->isBuiltInChannel($channelType),
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("获取通道信息失败", [
                    'channel_type' => $channelType,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $infos;
    }

    /**
     * 注销通道
     */
    public function unregisterChannel(string $channelType): void
    {
        if (isset($this->channels[$channelType])) {
            unset($this->channels[$channelType]);
            unset($this->channelInstances[$channelType]);

            Log::info("短信通道已注销", ['channel_type' => $channelType]);
        }
    }

    /**
     * 判断是否为内置通道
     */
    protected function isBuiltInChannel(string $channelType): bool
    {
        $builtInChannels = ['aliyun', 'tencent'];
        return in_array($channelType, $builtInChannels);
    }

    /**
     * 清除所有缓存的通道实例
     */
    public function clearInstanceCache(): void
    {
        $this->channelInstances = [];
    }

    /**
     * 获取注册的通道数量
     */
    public function count(): int
    {
        return count($this->channels);
    }
}
