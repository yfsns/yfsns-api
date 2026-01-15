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

namespace App\Modules\Sms\Infrastructure\Services;

use App\Modules\Sms\Models\SmsConfig;
use App\Modules\Sms\Models\SmsLog;
use App\Modules\Sms\Models\SmsTemplate;
use App\Modules\Sms\Config\SmsConfigManager;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsServiceImpl
{
    protected SmsConfigManager $configManager;

    public function __construct(SmsConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * 发送短信
     */
    public function send(string $phone, string $templateCode, array $templateData = [], ?string $driver = null): array
    {
        try {
            // 每次使用时重新获取配置
            if ($driver) {
                $config = SmsConfig::getByDriver($driver);
                if (! $config) {
                    throw new RuntimeException("短信驱动 {$driver} 的配置不存在或已禁用");
                }
            } else {
                $config = $this->getDefaultConfig();
            }

            // 获取短信模板
            $template = SmsTemplate::where('code', $templateCode)
                ->where('driver', $driver ?? $config->driver)
                ->where('status', 1)
                ->first();

            $tencentTemplate = null;
            if (!$template && ($driver === 'tencent' || $config->driver === 'tencent')) {
                // 如果是腾讯云驱动且没找到SmsTemplate，尝试在tencent_sms_templates表中查找
                if (\Illuminate\Support\Facades\Schema::hasTable('tencent_sms_templates')) {
                    $tencentTemplate = DB::table('tencent_sms_templates')
                        ->where('template_id', $templateCode)
                        ->where('status', 1)
                        ->first();
                }
            }

            if (!$template && !$tencentTemplate) {
                throw new RuntimeException('短信模板不存在或已禁用');
            }

            // 如果是腾讯云插件的模板，直接使用腾讯云插件发送
            if ($tencentTemplate && class_exists('Plugins\TencentSmsPlugin\Services\TencentSmsService')) {
                try {
                    $tencentSmsService = app('Plugins\TencentSmsPlugin\Services\TencentSmsService');
                    $result = $tencentSmsService->send($phone, $templateCode, $templateData);
                } catch (Exception $e) {
                    Log::error('腾讯云插件发送失败: ' . $e->getMessage());
                    $result = [
                        'success' => false,
                        'message' => '腾讯云插件发送失败: ' . $e->getMessage(),
                        'data' => null
                    ];
                }
            } else {
                // 使用SmsService统一发送，避免直接依赖SDK
                $smsService = app(\App\Modules\Sms\Services\SmsService::class);
                $result = $smsService->send($phone, $templateCode, $templateData, $driver ?? $config->driver);
            }

            // 记录日志
            $logData = [
                'phone' => $phone,
                'template_code' => $templateCode,
                'template_data' => $templateData,
                'driver' => $driver ?? $config->driver,
                'status' => $result['success'] ? 1 : 0,
                'error_message' => $result['success'] ? null : $result['message'],
                'response_data' => $result['data'] ?? null,
            ];

            if ($template) {
                $logData['content'] = $template->replaceVariables($templateData);
                $logData['template_id'] = $template->id;
            } elseif ($tencentTemplate) {
                $logData['content'] = $tencentTemplate->template_content;
                $logData['template_id'] = $tencentTemplate->id;
            }

            SmsLog::create($logData);

            return $result;
        } catch (Exception $e) {
            Log::error('短信发送失败：' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * 发送通知.
     */
    public function sendNotification(string $phone, string $title, string $content): array
    {
        return $this->send($phone, 'notification', [
            'title' => $title,
            'content' => $content,
        ]);
    }

    /**
     * 直接使用模板ID发送短信（不查找SmsTemplate表）
     * 用于通知模块，统一使用NotificationTemplate中的sms_template_id
     * 自动识别并使用本地驱动或插件驱动
     * 
     * @param string $phone 手机号
     * @param string $templateId 模板ID
     * @param array $templateData 模板数据（关联数组）
     * @param string|null $driver 指定驱动
     * @param array $variables 变量顺序数组，用于按顺序构建参数
     */
    public function sendWithTemplateId(string $phone, string $templateId, array $templateData = [], ?string $driver = null, array $variables = []): array
    {
        try {
            $usedDriver = null;
            $result = null;

            // 1. 如果有指定driver，尝试使用本地驱动
            if ($driver) {
                $config = SmsConfig::getByDriver($driver);
                if ($config && $config->status) {
                    $usedDriver = $driver;
                    $result = $this->sendWithLocalDriver($phone, $templateId, $templateData, $config);
                }
            } else {
                // 2. 尝试获取默认本地驱动配置
                try {
                    $config = $this->getDefaultConfig();
                    if ($config) {
                        $usedDriver = $config->driver;
                        $result = $this->sendWithLocalDriver($phone, $templateId, $templateData, $config);
                    }
                } catch (RuntimeException $e) {
                    // 没有本地驱动配置，继续尝试插件
                    Log::info('未找到本地短信驱动配置，尝试使用插件', ['error' => $e->getMessage()]);
                }
            }

            // 3. 如果没有本地驱动或发送失败，尝试使用腾讯云短信插件
            if (!$result || !$result['success']) {
                $pluginResult = $this->trySendWithTencentPlugin($phone, $templateId, $templateData, $variables);
                if ($pluginResult) {
                    $usedDriver = 'tencent-plugin';
                    $result = $pluginResult;
                }
            }

            // 4. 如果都没有可用驱动，返回错误
            if (!$result) {
                throw new RuntimeException('未找到可用的短信驱动（本地驱动或插件）');
            }

            // 记录日志
            $content = '';
            foreach ($templateData as $key => $value) {
                $content .= "{$key}: {$value}; ";
            }
            
            SmsLog::create([
                'phone' => $phone,
                'content' => $content ?: '短信通知',
                'template_id' => null, // 没有SmsTemplate记录
                'template_data' => $templateData,
                'driver' => $usedDriver ?? 'unknown',
                'status' => $result['success'] ? 1 : 0,
                'error_message' => $result['success'] ? null : $result['message'],
                'response_data' => $result['data'] ?? null,
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('短信发送失败：' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * 使用本地驱动发送短信
     */
    protected function sendWithLocalDriver(string $phone, string $templateId, array $templateData, SmsConfig $config): array
    {
        try {
            // 获取通道实例
            $channelRegistry = app(\App\Modules\Sms\Channels\Registry\SmsChannelRegistryInterface::class);
            $channel = $channelRegistry->getChannel($config->driver);
            
            if (! $channel) {
                throw new RuntimeException("短信通道 '{$config->driver}' 不存在");
            }

            // 设置通道配置
            $channel->setConfig($config->config);

            // 直接调用channel发送，使用模板ID作为templateCode
            return $channel->send($phone, $templateId, $templateData);
        } catch (Exception $e) {
            Log::warning('本地驱动发送失败，尝试插件', [
                'driver' => $config->driver,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 尝试使用腾讯云短信插件发送
     */
    protected function trySendWithTencentPlugin(string $phone, string $templateId, array $templateData, array $variables = []): ?array
    {
        try {
            // 检查插件类是否存在
            if (!class_exists('Plugins\TencentSmsPlugin\Services\TencentSmsService')) {
                return null;
            }

            // 检查插件是否启用
            $pluginManager = app(\App\Modules\PluginSystem\Services\PluginManagerService::class);
            $pluginStatus = $pluginManager->getPluginStatus('TencentSmsPlugin');
            
            if (!($pluginStatus['enabled'] ?? false)) {
                Log::info('腾讯云短信插件未启用');
                return null;
            }

            // 使用插件服务发送，传递模板ID、变量顺序和模板数据
            $tencentSmsService = app('Plugins\TencentSmsPlugin\Services\TencentSmsService');
            $result = $tencentSmsService->send($phone, $templateId, $templateData, $variables);

            Log::info('使用腾讯云短信插件发送', [
                'phone' => $phone,
                'template_id' => $templateId,
                'success' => $result['success'] ?? false
            ]);

            return $result;
        } catch (Exception $e) {
            Log::warning('腾讯云短信插件发送失败', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 获取当前启用的短信配置
     * （同时只能有一个启用）.
     */
    protected function getDefaultConfig()
    {
        $config = $this->configManager->getDefaultChannelConfig();
        if (! $config) {
            throw new RuntimeException('短信服务未配置或未启用');
        }

        return $config;
    }


}
