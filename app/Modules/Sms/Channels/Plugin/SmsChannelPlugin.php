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

use App\Modules\PluginSystem\BasePlugin;
use App\Modules\Sms\Channels\Registry\SmsChannelRegistryInterface;
use App\Modules\Sms\Contracts\SmsChannelPluginInterface;
use Illuminate\Support\Facades\Log;

abstract class SmsChannelPlugin extends BasePlugin implements SmsChannelPluginInterface
{
    protected PluginChannelBridge $channelBridge;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 初始化插件
     */
    protected function initialize(): void
    {
        // 短信插件的初始化逻辑
        $this->name = $this->getInfo()['name'];
        $this->version = $this->getInfo()['version'] ?? '1.0.0';
        $this->description = $this->getInfo()['description'] ?? '';
        $this->author = $this->getInfo()['author'] ?? 'Unknown';
    }

    /**
     * 启用插件
     */
    public function enable(): void
    {
        parent::enable();

        // 延迟初始化channelBridge
        $this->channelBridge = app(PluginChannelBridge::class);

        // 自动注册短信通道
        try {
            $registry = app(SmsChannelRegistryInterface::class);
            $this->registerSmsChannels($registry);

            Log::info("短信插件通道注册成功", [
                'plugin' => $this->getInfo()['name'],
                'channels' => $this->getSmsChannels()
            ]);
        } catch (\Exception $e) {
            Log::error("短信插件通道注册失败", [
                'plugin' => $this->getInfo()['name'],
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 禁用插件
     */
    public function disable(): void
    {
        // 注销短信通道（清理内存状态，立即生效）
        try {
            $pluginName = $this->getInfo()['name'];

            // 确保 channelBridge 已初始化
            if (!isset($this->channelBridge)) {
                $this->channelBridge = app(PluginChannelBridge::class);
            }

            $this->channelBridge->unregisterPluginChannels($pluginName);

            Log::info("短信插件通道已从内存中注销", ['plugin' => $pluginName]);
        } catch (\Exception $e) {
            Log::warning("短信插件通道注销失败", [
                'plugin' => $this->getInfo()['name'],
                'error' => $e->getMessage()
            ]);
            // 不抛出异常，确保插件禁用流程继续
        }

        parent::disable();
    }

    /**
     * 获取插件信息（扩展基础插件信息）
     * 带 smsplug 标签的插件可被 SMS 模块发现并注册为短信通道
     */
    public function getInfo(): array
    {
        $info = parent::getInfo();
        $info['type'] = 'sms_channel';
        $info['tags'] = array_merge($info['tags'] ?? [], ['smsplug']);
        $info['capabilities'] = ['sms_channel_provider'];
        return $info;
    }

    /**
     * 抽象方法：注册短信通道
     */
    abstract public function registerSmsChannels(SmsChannelRegistryInterface $registry): void;

    /**
     * 默认实现：获取插件提供的通道列表
     */
    public function getSmsChannels(): array
    {
        return [];
    }

    /**
     * 辅助方法：创建通道配置
     */
    protected function createChannelConfig(
        string $type,
        string $name,
        string $driverClass,
        string $description = '',
        array $capabilities = []
    ): array {
        return [
            'type' => $type,
            'name' => $name,
            'driver_class' => $driverClass,
            'description' => $description,
            'capabilities' => $capabilities,
            'is_builtin' => false,
            'plugin' => $this->getInfo()['name'],
        ];
    }

    /**
     * 辅助方法：注册单个通道
     */
    protected function registerChannel(
        SmsChannelRegistryInterface $registry,
        string $type,
        string $driverClass
    ): void {
        $pluginName = $this->getInfo()['name'];
        $pluginChannelType = "plugin.{$pluginName}.{$type}";

        $registry->registerChannel($pluginChannelType, $driverClass);

        Log::info("插件通道注册", [
            'plugin' => $pluginName,
            'channel_type' => $type,
            'registered_type' => $pluginChannelType,
            'driver_class' => $driverClass
        ]);
    }
}
