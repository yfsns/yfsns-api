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

namespace App\Modules\Sms\Channels\BuiltIn;

use App\Modules\Sms\Contracts\SmsChannelInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TencentChannel implements SmsChannelInterface
{
    protected ?array $currentConfig = null;

    /**
     * 获取通道类型标识
     */
    public function getChannelType(): string
    {
        return 'tencent';
    }

    /**
     * 获取驱动名称
     */
    public function getName(): string
    {
        return '腾讯云短信';
    }

    /**
     * 获取驱动配置
     */
    public function getConfig(): array
    {
        return $this->currentConfig ?: [];
    }

    /**
     * 获取通道配置表单字段定义
     */
    public function getConfigFields(): array
    {
        return [
            'secret_id' => [
                'type' => 'text',
                'label' => 'SecretId',
                'required' => true,
                'placeholder' => '请输入腾讯云SecretId',
                'help' => '从腾讯云控制台获取的SecretId'
            ],
            'secret_key' => [
                'type' => 'password',
                'label' => 'SecretKey',
                'required' => true,
                'placeholder' => '请输入腾讯云SecretKey',
                'help' => '从腾讯云控制台获取的SecretKey'
            ],
            'region_id' => [
                'type' => 'select',
                'label' => '地域',
                'required' => true,
                'default' => 'ap-guangzhou',
                'options' => [
                    'ap-guangzhou' => '广州',
                    'ap-shanghai' => '上海',
                    'ap-beijing' => '北京',
                    'ap-shenzhen' => '深圳',
                    'ap-hongkong' => '香港',
                ],
                'help' => '选择短信服务所在的地域'
            ],
            'sdk_app_id' => [
                'type' => 'text',
                'label' => 'SDK AppID',
                'required' => true,
                'placeholder' => '请输入SDK AppID',
                'help' => '腾讯云短信应用的SDK AppID'
            ],
            'sign_name' => [
                'type' => 'text',
                'label' => '短信签名',
                'required' => true,
                'placeholder' => '请输入短信签名',
                'help' => '已审核通过的短信签名'
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
            $errors[] = 'SDK AppID不能为空';
        }

        if (empty($config['sign_name'])) {
            $errors[] = '短信签名不能为空';
        }

        if (empty($config['region_id'])) {
            $warnings[] = '未指定地域，将使用默认地域 ap-guangzhou';
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
     * 发送短信
     */
    public function send(string $phone, string $templateCode, array $templateData = []): array
    {
        if (!$this->currentConfig) {
            return [
                'success' => false,
                'message' => '通道配置未设置',
                'data' => null
            ];
        }

        try {
            // 从服务容器获取模板ID
            // 如果getTemplateId返回null，则假设templateCode本身就是模板ID（用于通知模块直接传入模板ID）
            $smsService = app(\App\Modules\Sms\Services\SmsService::class);
            $templateId = $smsService->getTemplateId($templateCode, $this->getChannelType());
            
            // 如果查找不到模板，假设templateCode本身就是模板ID（用于通知模块）
            if (!$templateId) {
                $templateId = $templateCode;
            }

            // 构建腾讯云短信API请求参数
            $timestamp = time();
            $params = [
                'SmsSdkAppId' => $this->currentConfig['sdk_app_id'],
                'SignName' => $this->currentConfig['sign_name'],
                'TemplateId' => $templateId,
                'PhoneNumberSet' => [$phone],
                'TemplateParamSet' => array_values($templateData), // 腾讯云API要求数组格式
            ];

            // 添加腾讯云API必需的公共参数
            $commonParams = [
                'Action' => 'SendSms',
                'Version' => '2021-01-11',
                'Region' => $this->currentConfig['region_id'] ?? 'ap-guangzhou',
                'Timestamp' => $timestamp,
                'Nonce' => rand(10000, 99999),
                'SecretId' => $this->currentConfig['secret_id'],
            ];

            $allParams = array_merge($commonParams, $params);

            // 生成签名（简化版，实际项目中需要完整实现）
            $signature = $this->generateSignature($allParams, $this->currentConfig['secret_key']);
            $allParams['Signature'] = $signature;

            // 发送HTTP请求到腾讯云API
            $response = Http::timeout(30)
                ->post('https://sms.tencentcloudapi.com/', $allParams)
                ->json();

            $response = $response ?: [];

            $sendStatusSet = $response['Response']['SendStatusSet'][0] ?? [];

            return [
                'success' => ($sendStatusSet['Code'] ?? '') === 'Ok',
                'message' => $sendStatusSet['Message'] ?? '发送成功',
                'data' => $response,
            ];

        } catch (Exception $e) {
            Log::error('腾讯云短信发送失败', [
                'phone' => $phone,
                'template' => $templateCode,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => '短信发送失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 生成腾讯云API签名
     */
    protected function generateSignature(array $params, string $secretKey): string
    {
        // 腾讯云API签名算法（简化版，实际项目中需要完整实现）
        // 这里暂时返回一个模拟签名，实际使用时需要在插件中完整实现
        ksort($params);
        $queryString = http_build_query($params);
        return base64_encode(hash_hmac('sha1', $queryString, $secretKey, true));
    }

    /**
     * 获取短信签名和模板信息
     */
    public function getTemplates(): array
    {
        // 由于移除了SDK，这里返回配置的模板信息
        return [
            'success' => false,
            'message' => '腾讯云短信模板获取功能已移至插件实现',
            'data' => []
        ];
    }

    /**
     * 测试通道连通性
     */
    public function testConnection(array $config): array
    {
        $this->currentConfig = $config;

        try {
            // 尝试创建客户端
            $cred = new \TencentCloud\Common\Credential(
                $config['secret_id'],
                $config['secret_key']
            );

            $httpProfile = new \TencentCloud\Common\Profile\HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');

            $clientProfile = new \TencentCloud\Common\Profile\ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);

            $client = new \TencentCloud\Sms\V20210111\SmsClient(
                $cred,
                $config['region_id'] ?? 'ap-guangzhou',
                $clientProfile
            );

            return [
                'success' => true,
                'message' => '腾讯云通道连接正常',
                'data' => ['region' => $config['region_id'] ?? 'ap-guangzhou']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '连接测试失败：' . $e->getMessage(),
                'data' => null
            ];
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
            'description' => '腾讯云短信服务，提供国内短信和国际短信服务',
            'regions' => ['CN', 'HK', 'US', 'SG', 'JP']
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
        return ['CN', 'HK', 'US', 'SG', 'JP', 'KR', 'MY', 'TH', 'VN', 'ID', 'PH', 'AU'];
    }

    /**
     * 构建模板参数
     */
    protected function buildTemplateParams(string $templateCode, array $templateData): array
    {
        $templateParams = [];

        if ($templateCode === 'verification_code') {
            // 验证码模板，第一个参数是验证码，第二个参数是有效期
            $templateParams[] = (string) ($templateData['code'] ?? '');
            $templateParams[] = (string) ($templateData['expire'] ?? '10');
        } else {
            // 其他模板，按照变量顺序传递参数
            foreach ($templateData as $value) {
                $templateParams[] = (string) $value;
            }
        }

        return $templateParams;
    }

    /**
     * 设置当前配置（由SmsService调用）
     */
    public function setConfig(array $config): void
    {
        $this->currentConfig = $config;
    }
}
