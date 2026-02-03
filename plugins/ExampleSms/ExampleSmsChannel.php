<?php

/**
 * 示例短信通道实现
 */

namespace Plugins\ExampleSms;

use App\Modules\Sms\Contracts\SmsChannelInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExampleSmsChannel implements SmsChannelInterface
{
    protected ?array $currentConfig = null;

    /**
     * 获取通道类型标识
     */
    public function getChannelType(): string
    {
        return 'example';
    }

    /**
     * 获取驱动名称
     */
    public function getName(): string
    {
        return '示例短信通道';
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
            'api_url' => [
                'type' => 'text',
                'label' => 'API地址',
                'required' => true,
                'placeholder' => 'https://api.example.com/sms/send',
                'help' => '短信服务API地址'
            ],
            'api_key' => [
                'type' => 'text',
                'label' => 'API密钥',
                'required' => true,
                'placeholder' => '请输入API密钥',
                'help' => '从服务商获取的API密钥'
            ],
            'sender_id' => [
                'type' => 'text',
                'label' => '发送者ID',
                'required' => true,
                'placeholder' => 'EXAMPLE',
                'help' => '短信发送者标识'
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

        if (empty($config['api_url'])) {
            $errors[] = 'API地址不能为空';
        } elseif (!filter_var($config['api_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'API地址格式无效';
        }

        if (empty($config['api_key'])) {
            $errors[] = 'API密钥不能为空';
        }

        if (empty($config['sender_id'])) {
            $errors[] = '发送者ID不能为空';
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
        return ['verification', 'notification'];
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
            // 构建短信内容（这里是示例，实际应该从模板获取）
            $content = $this->buildMessageContent($templateCode, $templateData);

            // 调用示例API
            $response = Http::post($this->currentConfig['api_url'], [
                'api_key' => $this->currentConfig['api_key'],
                'sender_id' => $this->currentConfig['sender_id'],
                'phone' => $phone,
                'message' => $content,
            ]);

            $result = $response->json();

            // 检查响应（这里是示例响应格式）
            $success = ($result['status'] ?? '') === 'success';

            return [
                'success' => $success,
                'message' => $success ? '发送成功' : ($result['message'] ?? '发送失败'),
                'data' => $result,
            ];

        } catch (Exception $e) {
            Log::error('示例短信发送异常：' . $e->getMessage());
            return [
                'success' => false,
                'message' => '发送失败：' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * 测试通道连通性
     */
    public function testConnection(array $config): array
    {
        $this->currentConfig = $config;

        try {
            // 发送测试请求
            $response = Http::post($config['api_url'], [
                'api_key' => $config['api_key'],
                'test' => true,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => '示例通道连接正常',
                    'data' => ['api_url' => $config['api_url']]
                ];
            }

            return [
                'success' => false,
                'message' => '连接测试失败：HTTP ' . $response->status(),
                'data' => null
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
            'name' => '示例短信服务',
            'website' => 'https://example.com',
            'description' => '这是一个用于演示的示例短信服务',
            'regions' => ['CN', 'US', 'EU']
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
        return ['CN', 'US', 'EU', 'JP', 'KR'];
    }

    /**
     * 构建短信内容
     */
    protected function buildMessageContent(string $templateCode, array $templateData): string
    {
        // 这里是示例实现，实际应该从SmsTemplate获取模板内容
        switch ($templateCode) {
            case 'verification_code':
                return "您的验证码是：{$templateData['code']}，{$templateData['expire']}分钟内有效。";

            case 'notification':
                return $templateData['content'] ?? '系统通知';

            default:
                return '示例短信内容';
        }
    }

    /**
     * 设置当前配置
     */
    public function setConfig(array $config): void
    {
        $this->currentConfig = $config;
    }
}
