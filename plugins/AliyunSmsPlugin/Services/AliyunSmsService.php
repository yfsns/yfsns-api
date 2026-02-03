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

namespace Plugins\AliyunSmsPlugin\Services;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use App\Modules\Sms\Contracts\SmsChannelInterface;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * 阿里云短信服务
 */
class AliyunSmsService implements SmsChannelInterface
{
    protected ?array $currentConfig = null;

    /**
     * 获取通道类型标识
     */
    public function getChannelType(): string
    {
        return 'aliyun-plugin';
    }

    /**
     * 获取驱动名称
     */
    public function getName(): string
    {
        return '阿里云短信插件';
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

        if (!$this->currentConfig['enabled'] ?? true) {
            return [
                'success' => false,
                'message' => '阿里云短信服务已禁用',
                'data' => null
            ];
        }

        try {
            // 初始化阿里云客户端
            AlibabaCloud::accessKeyClient(
                $this->currentConfig['access_key_id'],
                $this->currentConfig['access_key_secret']
            )->regionId($this->currentConfig['region_id'])->asDefaultClient();

            // 获取模板ID（这里假设模板ID就是templateCode，或者需要映射）
            $templateId = $templateCode; // 在实际项目中可能需要从数据库查询

            // 发送短信
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->options([
                    'query' => [
                        'PhoneNumbers' => $phone,
                        'SignName' => $this->currentConfig['sign_name'],
                        'TemplateCode' => $templateId,
                        'TemplateParam' => json_encode($templateData),
                    ],
                ])
                ->request();

            $response = $result->toArray();

            return [
                'success' => ($response['Code'] ?? '') === 'OK',
                'message' => $response['Message'] ?? '发送成功',
                'data' => $response
            ];

        } catch (ClientException $e) {
            Log::error('阿里云短信客户端异常：' . $e->getMessage(), [
                'phone' => $phone,
                'template' => $templateCode
            ]);

            return [
                'success' => false,
                'message' => '客户端错误：' . $e->getMessage(),
                'data' => null,
            ];
        } catch (ServerException $e) {
            Log::error('阿里云短信服务端异常：' . $e->getMessage(), [
                'phone' => $phone,
                'template' => $templateCode
            ]);

            return [
                'success' => false,
                'message' => '服务端错误：' . $e->getMessage(),
                'data' => null,
            ];
        } catch (Exception $e) {
            Log::error('阿里云短信发送异常：' . $e->getMessage(), [
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
            // 初始化阿里云客户端
            AlibabaCloud::accessKeyClient(
                $this->currentConfig['access_key_id'],
                $this->currentConfig['access_key_secret']
            )->regionId($this->currentConfig['region_id'])->asDefaultClient();

            // 查询短信签名
            $signResult = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                ->version('2017-05-25')
                ->action('QuerySmsSign')
                ->method('POST')
                ->options([
                    'query' => [
                        'PageIndex' => 1,
                        'PageSize' => 50,
                    ],
                ])
                ->request();

            // 查询短信模板
            $templateResult = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                ->version('2017-05-25')
                ->action('QuerySmsTemplate')
                ->method('POST')
                ->options([
                    'query' => [
                        'PageIndex' => 1,
                        'PageSize' => 50,
                    ],
                ])
                ->request();

            $signData = $signResult->toArray();
            $templateData = $templateResult->toArray();

            return [
                'success' => true,
                'message' => '获取成功',
                'data' => [
                    'signs' => $signData['SmsSignList'] ?? [],
                    'templates' => $templateData['SmsTemplateList'] ?? []
                ]
            ];

        } catch (Exception $e) {
            Log::error('获取阿里云短信模板失败：' . $e->getMessage());

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
            'access_key_id' => [
                'type' => 'password',
                'label' => 'AccessKey ID',
                'required' => true,
                'description' => '阿里云AccessKey ID'
            ],
            'access_key_secret' => [
                'type' => 'password',
                'label' => 'AccessKey Secret',
                'required' => true,
                'description' => '阿里云AccessKey Secret'
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
                    'cn-hangzhou' => '华东1 (杭州)',
                    'cn-shanghai' => '华东2 (上海)',
                    'cn-beijing' => '华北2 (北京)',
                    'cn-shenzhen' => '华南1 (深圳)'
                ],
                'default' => 'cn-hangzhou',
                'description' => '阿里云地域节点'
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
            'name' => '阿里云',
            'website' => 'https://www.aliyun.com',
            'description' => '阿里巴巴集团旗下云计算品牌，提供全面的云服务',
            'regions' => ['cn-hangzhou', 'cn-shanghai', 'cn-beijing', 'cn-shenzhen', 'cn-chengdu']
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
     * 从插件配置加载配置
     */
    protected function loadPluginConfig(): array
    {
        try {
            $pluginManager = app(\App\Modules\PluginSystem\Services\PluginConfigManagerService::class);
            $pluginName = 'AliyunSmsPlugin';

            return [
                'access_key_id' => $pluginManager->getPluginConfigValue($pluginName, 'ALIYUN_SMS_ACCESS_KEY_ID', ''),
                'access_key_secret' => $pluginManager->getPluginConfigValue($pluginName, 'ALIYUN_SMS_ACCESS_KEY_SECRET', ''),
                'sign_name' => $pluginManager->getPluginConfigValue($pluginName, 'ALIYUN_SMS_SIGN_NAME', ''),
                'region_id' => $pluginManager->getPluginConfigValue($pluginName, 'ALIYUN_SMS_REGION_ID', 'cn-hangzhou'),
                'enabled' => $pluginManager->getPluginConfigValue($pluginName, 'ALIYUN_SMS_ENABLED', true),
            ];
        } catch (Exception $e) {
            Log::warning('无法加载阿里云短信插件配置，使用默认配置：' . $e->getMessage());

            return [
                'access_key_id' => '',
                'access_key_secret' => '',
                'sign_name' => '',
                'region_id' => 'cn-hangzhou',
                'enabled' => false,
            ];
        }
    }
}
