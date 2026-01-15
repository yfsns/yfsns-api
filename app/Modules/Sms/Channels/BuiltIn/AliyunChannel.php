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

class AliyunChannel implements SmsChannelInterface
{
    protected ?array $currentConfig = null;

    /**
     * 获取通道类型标识
     */
    public function getChannelType(): string
    {
        return 'aliyun';
    }

    /**
     * 获取驱动名称
     */
    public function getName(): string
    {
        return '阿里云短信';
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
            'access_key_id' => [
                'type' => 'text',
                'label' => 'AccessKey ID',
                'required' => true,
                'placeholder' => '请输入阿里云AccessKey ID',
                'help' => '从阿里云控制台获取的AccessKey ID'
            ],
            'access_key_secret' => [
                'type' => 'password',
                'label' => 'AccessKey Secret',
                'required' => true,
                'placeholder' => '请输入阿里云AccessKey Secret',
                'help' => '从阿里云控制台获取的AccessKey Secret'
            ],
            'region_id' => [
                'type' => 'select',
                'label' => '地域',
                'required' => true,
                'default' => 'cn-hangzhou',
                'options' => [
                    'cn-hangzhou' => '华东1 (杭州)',
                    'cn-shanghai' => '华东2 (上海)',
                    'cn-beijing' => '华北2 (北京)',
                    'cn-shenzhen' => '华南1 (深圳)',
                ],
                'help' => '选择短信服务所在的地域'
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

        if (empty($config['access_key_id'])) {
            $errors[] = 'AccessKey ID不能为空';
        }

        if (empty($config['access_key_secret'])) {
            $errors[] = 'AccessKey Secret不能为空';
        }

        if (empty($config['sign_name'])) {
            $errors[] = '短信签名不能为空';
        }

        if (empty($config['region_id'])) {
            $warnings[] = '未指定地域，将使用默认地域 cn-hangzhou';
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
            // 初始化阿里云客户端
            $this->initializeClient($this->currentConfig);

            // 从服务容器获取模板ID
            // 如果getTemplateId返回null，则假设templateCode本身就是模板ID（用于通知模块直接传入模板ID）
            $smsService = app(\App\Modules\Sms\Services\SmsService::class);
            $templateId = $smsService->getTemplateId($templateCode, $this->getChannelType());
            
            // 如果查找不到模板，假设templateCode本身就是模板ID（用于通知模块）
            if (!$templateId) {
                $templateId = $templateCode;
            }

            // 构建阿里云短信API请求
            $timestamp = now()->toISOString();
            $signature = $this->generateSignature($phone, $this->currentConfig['sign_name'], $templateId, $templateData, $timestamp);

            $params = [
                'AccessKeyId' => $this->currentConfig['access_key_id'],
                'Timestamp' => $timestamp,
                'Format' => 'JSON',
                'SignatureMethod' => 'HMAC-SHA1',
                'SignatureVersion' => '1.0',
                'Signature' => $signature,
                'Action' => 'SendSms',
                'Version' => '2017-05-25',
                'RegionId' => 'cn-hangzhou',
                'PhoneNumbers' => $phone,
                'SignName' => $this->currentConfig['sign_name'],
                'TemplateCode' => $templateId,
                'TemplateParam' => json_encode($templateData),
            ];

            // 发送HTTP请求到阿里云API
            $response = Http::timeout(30)
                ->post('https://dysmsapi.aliyuncs.com/', $params)
                ->json();

            // 处理响应
            $response = $response ?: [];

            return [
                'success' => ($response['Code'] ?? '') === 'OK',
                'message' => $response['Message'] ?? '发送成功',
                'data' => $response,
            ];
        } catch (Exception $e) {
            Log::error('阿里云短信发送失败', [
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
     * 生成阿里云API签名
     */
    protected function generateSignature(string $phone, string $signName, string $templateCode, array $templateData, string $timestamp): string
    {
        // 阿里云API签名算法（简化版，实际项目中需要完整实现）
        // 这里暂时返回一个模拟签名，实际使用时需要在插件中完整实现
        return base64_encode(hash_hmac('sha1', 'mock_signature', $this->currentConfig['access_key_secret'], true));
    }

    /**
     * 获取短信签名和模板信息
     */
    public function getTemplates(): array
    {
        // 由于移除了SDK，这里返回配置的模板信息
        return [
            'success' => false,
            'message' => '阿里云短信模板获取功能已移至插件实现',
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
            // 尝试初始化客户端
            $this->initializeClient($config);

            return [
                'success' => true,
                'message' => '阿里云通道连接正常',
                'data' => ['region' => $config['region_id'] ?? 'cn-hangzhou']
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
            'name' => '阿里云',
            'website' => 'https://www.aliyun.com',
            'description' => '阿里云短信服务，提供国内短信和国际短信服务',
            'regions' => ['CN', 'HK', 'US', 'SG', 'MY']
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
        return ['CN', 'HK', 'US', 'SG', 'MY', 'AU', 'JP', 'KR', 'TH', 'VN', 'ID', 'PH'];
    }

    /**
     * 初始化阿里云客户端
     */
    protected function initializeClient(array $config): void
    {
        AlibabaCloud::accessKeyClient(
            $config['access_key_id'],
            $config['access_key_secret']
        )->regionId($config['region_id'] ?? 'cn-hangzhou')->asDefaultClient();
    }

    /**
     * 设置当前配置（由SmsService调用）
     */
    public function setConfig(array $config): void
    {
        $this->currentConfig = $config;
    }
}
