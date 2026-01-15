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

namespace App\Modules\Sms\Channels\Plugin;

use App\Modules\Sms\Channels\Registry\SmsChannelRegistryInterface;
use App\Modules\Sms\Contracts\SmsChannelInterface;
use Illuminate\Support\Facades\Log;

class PluginChannelBridge
{
    protected SmsChannelRegistryInterface $channelRegistry;

    public function __construct(SmsChannelRegistryInterface $channelRegistry)
    {
        $this->channelRegistry = $channelRegistry;
    }

    /**
     * 注册插件通道
     *
     * @param string $pluginName 插件名称
     * @param array $channels 通道配置数组
     * @return array 注册结果
     */
    public function registerPluginChannels(string $pluginName, array $channels): array
    {
        $registered = [];
        $failed = [];

        foreach ($channels as $channelConfig) {
            try {
                $result = $this->registerSingleChannel($pluginName, $channelConfig);
                if ($result['success']) {
                    $registered[] = $result['channel_type'];
                } else {
                    $failed[] = [
                        'channel_type' => $channelConfig['type'] ?? 'unknown',
                        'error' => $result['error']
                    ];
                }
            } catch (\Exception $e) {
                $failed[] = [
                    'channel_type' => $channelConfig['type'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];
                Log::error("插件 {$pluginName} 注册通道失败", [
                    'channel_config' => $channelConfig,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info("插件 {$pluginName} 通道注册完成", [
            'registered' => $registered,
            'failed' => $failed
        ]);

        return [
            'plugin' => $pluginName,
            'registered' => $registered,
            'failed' => $failed,
            'total_registered' => count($registered),
            'total_failed' => count($failed)
        ];
    }

    /**
     * 注册单个通道
     */
    protected function registerSingleChannel(string $pluginName, array $channelConfig): array
    {
        // 验证通道配置
        $validation = $this->validateChannelConfig($channelConfig);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'channel_type' => $channelConfig['type'] ?? 'unknown',
                'error' => '配置验证失败: ' . implode(', ', $validation['errors'])
            ];
        }

        $channelType = $channelConfig['type'];
        $driverClass = $channelConfig['driver_class'];

        // 检查驱动类是否存在且实现了正确的接口
        if (!class_exists($driverClass)) {
            return [
                'success' => false,
                'channel_type' => $channelType,
                'error' => "驱动类 {$driverClass} 不存在"
            ];
        }

        if (!is_subclass_of($driverClass, SmsChannelInterface::class)) {
            return [
                'success' => false,
                'channel_type' => $channelType,
                'error' => "驱动类 {$driverClass} 必须实现 SmsChannelInterface"
            ];
        }

        // 生成插件通道标识（防止与内置通道冲突）
        $pluginChannelType = $this->generatePluginChannelType($pluginName, $channelType);

        // 注册通道
        $this->channelRegistry->registerChannel($pluginChannelType, $driverClass);

        Log::info("插件通道注册成功", [
            'plugin' => $pluginName,
            'original_type' => $channelType,
            'registered_type' => $pluginChannelType,
            'driver_class' => $driverClass
        ]);

        return [
            'success' => true,
            'channel_type' => $pluginChannelType,
            'original_type' => $channelType,
            'driver_class' => $driverClass
        ];
    }

    /**
     * 注销插件通道
     */
    public function unregisterPluginChannels(string $pluginName, array $channelTypes = []): array
    {
        $unregistered = [];
        $failed = [];

        if (empty($channelTypes)) {
            // 注销插件的所有通道
            $channelTypes = $this->getPluginChannelTypes($pluginName);
        }

        foreach ($channelTypes as $channelType) {
            $pluginChannelType = $this->generatePluginChannelType($pluginName, $channelType);

            try {
                $this->channelRegistry->unregisterChannel($pluginChannelType);
                $unregistered[] = $pluginChannelType;
            } catch (\Exception $e) {
                $failed[] = [
                    'channel_type' => $pluginChannelType,
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info("插件 {$pluginName} 通道注销完成", [
            'unregistered' => $unregistered,
            'failed' => $failed
        ]);

        return [
            'plugin' => $pluginName,
            'unregistered' => $unregistered,
            'failed' => $failed
        ];
    }

    /**
     * 获取插件的所有通道类型
     */
    public function getPluginChannelTypes(string $pluginName): array
    {
        $allChannels = $this->channelRegistry->getAvailableChannels();
        $pluginChannels = [];

        $prefix = "plugin.{$pluginName}.";
        foreach (array_keys($allChannels) as $channelType) {
            if (str_starts_with($channelType, $prefix)) {
                // 移除前缀获取原始通道类型
                $originalType = str_replace($prefix, '', $channelType);
                $pluginChannels[] = $originalType;
            }
        }

        return $pluginChannels;
    }

    /**
     * 验证通道配置
     */
    protected function validateChannelConfig(array $config): array
    {
        $errors = [];

        if (empty($config['type'])) {
            $errors[] = '通道类型(type)不能为空';
        }

        if (empty($config['driver_class'])) {
            $errors[] = '驱动类(driver_class)不能为空';
        }

        if (empty($config['name'])) {
            $errors[] = '通道名称(name)不能为空';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 生成插件通道类型标识
     */
    protected function generatePluginChannelType(string $pluginName, string $channelType): string
    {
        return "plugin.{$pluginName}.{$channelType}";
    }

    /**
     * 检查是否为插件通道
     */
    public function isPluginChannel(string $channelType): bool
    {
        return str_starts_with($channelType, 'plugin.');
    }

    /**
     * 从插件通道类型中提取插件名称
     */
    public function extractPluginName(string $channelType): ?string
    {
        if (!$this->isPluginChannel($channelType)) {
            return null;
        }

        $parts = explode('.', $channelType);
        return $parts[1] ?? null;
    }

    /**
     * 从插件通道类型中提取原始通道类型
     */
    public function extractOriginalChannelType(string $channelType): ?string
    {
        if (!$this->isPluginChannel($channelType)) {
            return null;
        }

        $parts = explode('.', $channelType);
        array_shift($parts); // 移除 'plugin'
        array_shift($parts); // 移除 pluginName
        return implode('.', $parts);
    }
}
