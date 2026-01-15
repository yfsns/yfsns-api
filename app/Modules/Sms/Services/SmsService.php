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

namespace App\Modules\Sms\Services;

use App\Modules\Sms\Channels\Registry\SmsChannelRegistryInterface;
use App\Modules\Sms\Config\SmsConfigManager;
use App\Modules\Sms\Models\SmsLog;
use App\Modules\Sms\Models\SmsTemplate;

class SmsService
{
    protected SmsChannelRegistryInterface $channelRegistry;
    protected SmsConfigManager $configManager;

    public function __construct(
        SmsChannelRegistryInterface $channelRegistry,
        SmsConfigManager $configManager
    ) {
        $this->channelRegistry = $channelRegistry;
        $this->configManager = $configManager;

        // 注意：插件通道初始化已移至SmsServiceProvider::boot()中执行
        // 以避免每次请求都重复执行插件操作
    }

    /**
     * 初始化插件通道
     */
    protected function initializePluginChannels(): void
    {
        // 插件系统已禁用，跳过插件通道初始化
        return;

        // 以下代码已禁用，因为插件系统被移除
        // try {
        //     $pluginManager = app(\App\Modules\PluginSystem\Services\PluginManager::class);
        //     // 获取所有已启用的插件
        //     $enabledPlugins = $pluginManager->getEnabledPlugins();
        //     foreach ($enabledPlugins as $pluginName => $plugin) {
        //         // 如果插件实现了短信通道接口，则注册其通道
        //         if ($plugin instanceof \App\Modules\Sms\Contracts\SmsChannelPluginInterface) {
        //             try {
        //                 $plugin->registerSmsChannels($this->channelRegistry);
        //                 \Illuminate\Support\Facades\Log::info("插件短信通道初始化成功", ['plugin' => $pluginName]);
        //             } catch (\Exception $e) {
        //                 \Illuminate\Support\Facades\Log::error("插件短信通道初始化失败", [
        //                     'plugin' => $pluginName,
        //                     'error' => $e->getMessage()
        //                 ]);
        //             }
        //         }
        //     }
        // } catch (\Exception $e) {
        //     \Illuminate\Support\Facades\Log::warning("初始化插件通道时出错", [
        //         'error' => $e->getMessage()
        //     ]);
        // }
    }

    /**
     * 发送短信（统一入口）
     *
     * @param string $phone 手机号
     * @param string $templateCode 模板代码
     * @param array $data 模板数据
     * @param string|null $channelType 指定通道类型，不传则使用默认通道
     * @return array 发送结果
     */
    public function send(string $phone, string $templateCode, array $data = [], ?string $channelType = null): array
    {
        // 获取通道配置
        $channelConfig = $channelType
            ? $this->configManager->getChannelConfig($channelType)
            : $this->configManager->getDefaultChannelConfig();

        if (!$channelConfig) {
            $errorMsg = $channelType
                ? "短信通道 '{$channelType}' 未配置或未启用"
                : '默认短信通道未配置或未启用';

            return [
                'success' => false,
                'message' => $errorMsg,
                'data' => null,
            ];
        }

        // 获取通道实例
        $channel = $this->channelRegistry->getChannel($channelConfig->driver);
        if (!$channel) {
            return [
                'success' => false,
                'message' => "短信通道 '{$channelConfig->driver}' 不存在",
                'data' => null,
            ];
        }

        // 获取并验证模板
        $template = $this->getSmsTemplate($templateCode, $channelConfig->driver);
        if (!$template) {
            return [
                'success' => false,
                'message' => '短信模板不存在或已禁用',
                'data' => null,
            ];
        }

        // 发送短信
        $result = $channel->send($phone, $templateCode, $data);

        // 记录发送日志
        $this->logSmsSend($phone, $template, $data, $channelConfig, $result);

        return $result;
    }

    /**
     * 发送通知短信
     */
    public function sendNotification(string $phone, string $title, string $content, ?string $channelType = null): array
    {
        return $this->send($phone, 'notification', [
            'title' => $title,
            'content' => $content,
        ], $channelType);
    }

    /**
     * 发送验证码短信
     */
    public function sendVerificationCode(string $phone, string $code, int $expireMinutes = 10, ?string $channelType = null): array
    {
        return $this->send($phone, 'verification_code', [
            'code' => $code,
            'expire' => $expireMinutes,
        ], $channelType);
    }

    /**
     * 获取可用通道列表
     */
    public function getAvailableChannels(): array
    {
        return $this->channelRegistry->getChannelInfos();
    }

    /**
     * 获取通道状态
     */
    public function getChannelStatus(string $channelType): array
    {
        return $this->configManager->getChannelStatus($channelType);
    }

    /**
     * 获取所有通道状态
     */
    public function getAllChannelStatuses(): array
    {
        return $this->configManager->getAllChannelStatuses();
    }

    /**
     * 测试通道配置
     */
    public function testChannel(string $channelType, array $config = null): array
    {
        if ($config === null) {
            $channelConfig = $this->configManager->getChannelConfig($channelType);
            if (!$channelConfig) {
                return [
                    'success' => false,
                    'message' => "通道 '{$channelType}' 未配置"
                ];
            }
            $config = $channelConfig->config;
        }

        return $this->configManager->testChannelConfig($channelType, $config);
    }

    /**
     * 获取短信模板
     */
    protected function getSmsTemplate(string $templateCode, string $channelType): ?SmsTemplate
    {
        return SmsTemplate::where('code', $templateCode)
            ->where('driver', $channelType)
            ->where('status', 1)
            ->first();
    }

    /**
     * 获取模板ID（用于第三方服务）
     */
    public function getTemplateId(string $templateCode, string $channelType): ?string
    {
        $template = $this->getSmsTemplate($templateCode, $channelType);
        return $template ? $template->template_id : null;
    }

    /**
     * 记录短信发送日志
     */
    protected function logSmsSend(string $phone, SmsTemplate $template, array $data, $channelConfig, array $result): void
    {
        SmsLog::createFromSmsSend($phone, $template, $data, $channelConfig, $result);
    }
}
