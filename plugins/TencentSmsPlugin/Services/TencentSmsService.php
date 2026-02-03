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

namespace Plugins\TencentSmsPlugin\Services;

use App\Modules\Sms\Contracts\SmsChannelInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Sms\V20210111\Models\SendSmsRequest;
use TencentCloud\Sms\V20210111\SmsClient;

/**
 * 腾讯云短信服务
 */
class TencentSmsService implements SmsChannelInterface
{
    protected ?array $currentConfig = null;

    /**
     * 获取通道类型标识
     */
    public function getChannelType(): string
    {
        return 'tencent-plugin';
    }

    /**
     * 获取驱动名称
     */
    public function getName(): string
    {
        return '腾讯云短信插件';
    }

    /**
     * 获取驱动配置
     */
    public function getConfig(): array
    {
        if (!$this->currentConfig) {
            $this->currentConfig = $this->loadPluginConfig();
        }

        return $this->currentConfig;
    }

    /**
     * 设置驱动配置
     */
    public function setConfig(array $config): void
    {
        $this->currentConfig = $config;
    }

    /**
     * 发送短信
     * 
     * @param string $phone 手机号
     * @param string $templateCode 模板代码或模板ID
     * @param array $templateData 模板数据
     * @param array $variables 变量顺序数组（用于通知系统，按顺序构建参数）
     */
    public function send(string $phone, string $templateCode, array $templateData = [], array $variables = []): array
    {
        // 确保配置已加载
        if (!$this->currentConfig) {
            $this->currentConfig = $this->loadPluginConfig();
        }

        if (!$this->currentConfig || empty($this->currentConfig['secret_id'])) {
            return [
                'success' => false,
                'message' => '通道配置未设置',
                'data' => null
            ];
        }

        if (!($this->currentConfig['enabled'] ?? true)) {
            return [
                'success' => false,
                'message' => '腾讯云短信服务已禁用',
                'data' => null
            ];
        }

        try {
            // 初始化腾讯云客户端
            $cred = new Credential(
                $this->currentConfig['secret_id'],
                $this->currentConfig['secret_key']
            );

            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);

            $client = new SmsClient(
                $cred,
                $this->currentConfig['region_id'],
                $clientProfile
            );

            // 直接使用传入的templateCode作为模板ID（通知系统已传入模板ID）
            // 如果是纯数字，直接使用；否则尝试解析（兼容旧代码）
            $templateId = is_numeric($templateCode) ? $templateCode : $this->resolveTemplateId($templateCode);

            // 构建请求参数
            $req = new SendSmsRequest();
            $req->SmsSdkAppId = $this->currentConfig['sdk_app_id'];
            $req->SignName = $this->currentConfig['sign_name'];
            $req->TemplateId = $templateId;

            // 按照模板变量的顺序构建参数数组
            // 如果传入了variables，使用它来按顺序构建参数；否则使用默认方式
            $templateParams = $this->buildTemplateParams($templateId, $templateData, $variables ?? []);
            
            // 记录实际发送的参数，用于调试
            Log::info('腾讯云短信发送参数', [
                'template_id' => $templateId,
                'template_data' => $templateData,
                'variables' => $variables ?? [],
                'template_params' => $templateParams,
                'params_count' => count($templateParams),
            ]);
            
            $req->TemplateParamSet = $templateParams;
            $req->PhoneNumberSet = ['+86' . $phone];

            // 发送短信
            $resp = $client->SendSms($req);
            $response = json_decode($resp->toJsonString(), true);

            $sendStatusSet = $response['SendStatusSet'][0] ?? [];

            return [
                'success' => ($sendStatusSet['Code'] ?? '') === 'Ok',
                'message' => $sendStatusSet['Message'] ?? '发送成功',
                'data' => $response,
            ];

        } catch (TencentCloudSDKException $e) {
            Log::error('腾讯云短信SDK异常：' . $e->getMessage(), [
                'phone' => $phone,
                'template' => $templateCode
            ]);

            return [
                'success' => false,
                'message' => 'SDK错误：' . $e->getMessage(),
                'data' => null,
            ];
        } catch (Exception $e) {
            Log::error('腾讯云短信发送异常：' . $e->getMessage(), [
                'phone' => $phone,
                'template' => $templateCode
            ]);

            return [
                'success' => false,
                'message' => '发送失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 获取短信签名和模板信息
     */
    public function getTemplates(): array
    {
        try {
            // 初始化腾讯云客户端
            $cred = new Credential(
                $this->currentConfig['secret_id'],
                $this->currentConfig['secret_key']
            );

            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);

            $client = new SmsClient(
                $cred,
                $this->currentConfig['region_id'],
                $clientProfile
            );

            // 这里可以实现查询签名和模板的逻辑
            // 腾讯云短信API支持查询签名和模板列表

            return [
                'success' => false,
                'message' => '腾讯云短信模板查询功能待实现',
                'data' => []
            ];

        } catch (Exception $e) {
            Log::error('获取腾讯云短信模板失败：' . $e->getMessage());

            return [
                'success' => false,
                'message' => '获取失败：' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * 获取通道配置表单字段定义
     */
    public function getConfigFields(): array
    {
        return [
            'secret_id' => [
                'type' => 'password',
                'label' => 'SecretId',
                'required' => true,
                'description' => '腾讯云SecretId'
            ],
            'secret_key' => [
                'type' => 'password',
                'label' => 'SecretKey',
                'required' => true,
                'description' => '腾讯云SecretKey'
            ],
            'sdk_app_id' => [
                'type' => 'text',
                'label' => 'SDK AppId',
                'required' => true,
                'description' => '腾讯云短信SDK AppId'
            ],
            'sign_name' => [
                'type' => 'text',
                'label' => '短信签名',
                'required' => true,
                'description' => '短信签名名称'
            ],
            'region_id' => [
                'type' => 'select',
                'label' => '地域节点',
                'required' => false,
                'options' => [
                    'ap-guangzhou' => '广州',
                    'ap-beijing' => '北京',
                    'ap-shanghai' => '上海',
                    'ap-chengdu' => '成都',
                    'ap-chongqing' => '重庆'
                ],
                'default' => 'ap-guangzhou',
                'description' => '腾讯云地域节点'
            ]
        ];
    }

    /**
     * 验证通道配置
     */
    public function validateConfig(array $config): array
    {
        $errors = [];
        $warnings = [];

        if (empty($config['secret_id'])) {
            $errors[] = 'SecretId不能为空';
        }

        if (empty($config['secret_key'])) {
            $errors[] = 'SecretKey不能为空';
        }

        if (empty($config['sdk_app_id'])) {
            $errors[] = 'SDK AppId不能为空';
        }

        if (empty($config['sign_name'])) {
            $errors[] = '短信签名不能为空';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * 获取通道特性能力
     */
    public function getCapabilities(): array
    {
        return ['verification', 'notification', 'marketing'];
    }

    /**
     * 测试通道连通性
     */
    public function testConnection(array $config): array
    {
        // 保存当前配置
        $originalConfig = $this->currentConfig;
        $this->currentConfig = $config;

        try {
            // 尝试发送一条测试短信（这里使用虚拟号码和模板）
            $result = $this->send('13800138000', 'test_template', ['code' => '000000']);

            return [
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '连接测试失败：' . $e->getMessage(),
                'data' => null
            ];
        } finally {
            // 恢复原始配置
            $this->currentConfig = $originalConfig;
        }
    }

    /**
     * 获取通道提供商信息
     */
    public function getProviderInfo(): array
    {
        return [
            'name' => '腾讯云',
            'website' => 'https://cloud.tencent.com',
            'description' => '腾讯云是腾讯集团倾力打造的云计算品牌，提供全栈云服务',
            'regions' => ['ap-guangzhou', 'ap-beijing', 'ap-shanghai', 'ap-chengdu', 'ap-chongqing']
        ];
    }

    /**
     * 是否支持国际短信
     */
    public function supportsInternational(): bool
    {
        return true;
    }

    /**
     * 获取支持的国家/地区列表
     */
    public function getSupportedRegions(): array
    {
        return ['CN', 'HK', 'TW', 'US', 'SG', 'MY', 'JP', 'KR', 'TH', 'VN', 'ID', 'PH'];
    }

    /**
     * 解析模板ID
     */
    protected function resolveTemplateId(string $templateCode): string
    {
        try {
            // 首先尝试直接使用templateCode作为模板ID
            if (is_numeric($templateCode)) {
                return $templateCode;
            }

            // 如果是字符串，尝试从本地模板表查找
            $templateSyncService = app(TencentTemplateSyncService::class);
            $template = $templateSyncService->findTemplate($templateCode);

            if ($template) {
                return $template->template_id;
            }

            // 如果找不到，假设templateCode就是模板ID
            return $templateCode;

        } catch (Exception $e) {
            Log::warning('解析模板ID失败，使用原始值：' . $e->getMessage(), [
                'template_code' => $templateCode
            ]);
            return $templateCode;
        }
    }

    /**
     * 构建腾讯云短信模板参数
     * 
     * @param string $templateCodeOrId 模板代码或模板ID
     * @param array $templateData 模板数据（关联数组）
     * @param array $variables 变量顺序数组（从NotificationTemplate传入）
     */
    protected function buildTemplateParams(string $templateCodeOrId, array $templateData, array $variables = []): array
    {
        // 如果传入了variables，按照variables的顺序构建参数数组
        if (!empty($variables)) {
            $orderedParams = [];
            foreach ($variables as $varName) {
                if (isset($templateData[$varName])) {
                    $orderedParams[] = (string) $templateData[$varName];
                }
            }
            // 如果按variables顺序找到了参数，返回它们
            if (!empty($orderedParams)) {
                return $orderedParams;
            }
        }

        // 如果是模板ID（纯数字），说明是通知系统传入的
        // 参数可能是索引数组（已排序）或关联数组（需要按顺序提取）
        if (is_numeric($templateCodeOrId)) {
            // 如果已经是索引数组，直接使用；否则转换为索引数组
            if (array_values($templateData) === $templateData) {
                // 已经是索引数组，直接转换为字符串
                return array_map('strval', $templateData);
            } else {
                // 关联数组，按值顺序提取
                return array_map('strval', array_values($templateData));
            }
        }

        // 如果是模板代码（字符串），根据模板代码进行特殊处理（兼容旧代码）
        if ($templateCodeOrId === 'verification_code') {
            // 验证码模板，第一个参数是验证码，第二个参数是有效期
            return [
                (string) ($templateData['code'] ?? ''),
                (string) ($templateData['expire'] ?? '10')
            ];
        }

        // 默认处理：按照变量顺序传递参数
        return array_map('strval', array_values($templateData));
    }

    /**
     * 从插件配置加载配置
     */
    protected function loadPluginConfig(): array
    {
        try {
            $pluginManager = app(\App\Modules\PluginSystem\Services\PluginConfigManagerService::class);
            $pluginName = 'TencentSmsPlugin';

            return [
                'secret_id' => $pluginManager->getPluginConfigValue($pluginName, 'TENCENT_SMS_SECRET_ID', ''),
                'secret_key' => $pluginManager->getPluginConfigValue($pluginName, 'TENCENT_SMS_SECRET_KEY', ''),
                'sdk_app_id' => $pluginManager->getPluginConfigValue($pluginName, 'TENCENT_SMS_SDK_APP_ID', ''),
                'sign_name' => $pluginManager->getPluginConfigValue($pluginName, 'TENCENT_SMS_SIGN_NAME', ''),
                'region_id' => $pluginManager->getPluginConfigValue($pluginName, 'TENCENT_SMS_REGION_ID', 'ap-guangzhou'),
                'enabled' => $pluginManager->getPluginConfigValue($pluginName, 'TENCENT_SMS_ENABLED', true),
            ];
        } catch (Exception $e) {
            Log::warning('无法加载腾讯云短信插件配置，使用默认配置：' . $e->getMessage());

            return [
                'secret_id' => '',
                'secret_key' => '',
                'sdk_app_id' => '',
                'sign_name' => '',
                'region_id' => 'ap-guangzhou',
                'enabled' => false,
            ];
        }
    }
}
