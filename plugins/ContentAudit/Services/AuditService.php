<?php

namespace Plugins\ContentAudit\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Modules\PluginSystem\Services\PluginConfigManagerService;

use function is_array;

use RuntimeException;

class AuditService
{
    protected Client $httpClient;

    protected PluginConfigManagerService $configManager;

    protected string $pluginName = 'ContentAudit';

    public function __construct(PluginConfigManagerService $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * 获取插件配置值
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->configManager->getPluginConfigValue($this->pluginName, $key, $default);
    }

    /**
     * 初始化HTTP客户端
     */
    protected function initializeHttpClient(): void
    {
        // 从配置管理器获取API基础URL
        $baseUrl = $this->getConfig('AUDIT_API_BASE_URL', 'http://127.0.0.1:8001/api/tenant/ai-moderation');

        if ($baseUrl) {
            // 确保 base_uri 以斜杠结尾，以便正确拼接路径
            $baseUrl = rtrim($baseUrl, '/') . '/';

            $timeout = (int) $this->getConfig('AUDIT_API_TIMEOUT', 30);

            $this->httpClient = new Client([
                'base_uri' => $baseUrl,
                'timeout' => $timeout,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);
        }
    }

    /**
     * 延迟初始化 - 在首次使用时初始化HTTP客户端
     */
    protected function ensureHttpClient(): void
    {
        if (!isset($this->httpClient)) {
            $this->initializeHttpClient();
        }
    }

    /**
     * 审核内容（只接收数据，返回结果，不操作主应用数据）.
     *
     * @param array  $contentData 内容数据
     * @param string $contentType 内容类型
     *
     * @return null|array 审核结果
     */
    public function auditContent(array $contentData, string $contentType = 'article'): array
    {
        if (! isset($this->httpClient)) {
            throw new RuntimeException('审核服务未配置，请检查插件配置');
        }

        try {
            $apiToken = $this->getConfig('AUDIT_API_TOKEN', '');
            if (! $apiToken) {
                throw new RuntimeException('审核服务API Token未配置，请在插件设置中配置Token');
            }

            // 构建审核文本
            $text = trim(implode(' ', array_filter([
                $contentData['title'] ?? '',
                $contentData['content'] ?? '',
                $contentData['description'] ?? '',
            ])));

            if (empty($text)) {
                throw new RuntimeException('审核内容为空');
            }

            // 发送审核请求
            $response = $this->httpClient->post('check', [
                'headers' => ['Authorization' => 'Bearer ' . $apiToken],
                'json' => ['content' => $text, 'type' => 'text'],
            ]);

            $responseBody = $response->getBody()->getContents();
            $result = json_decode($responseBody, true);

            // 检查响应是否有效
            if ($result === null || ! is_array($result)) {
                Log::error('审核服务返回无效响应', [
                    'response_body' => $responseBody,
                    'status_code' => $response->getStatusCode(),
                ]);

                throw new RuntimeException('审核服务返回无效响应: ' . ($responseBody ?: '空响应'));
            }

            // 转换并返回结果
            return $this->transformAuditResponse($result);
        } catch (ConnectException $e) {
            throw new RuntimeException('审核服务连接失败: ' . $this->formatConnectionError($e), 0, $e);
        } catch (RequestException $e) {
            throw new RuntimeException('审核服务请求失败: ' . $this->formatRequestError($e), 0, $e);
        } catch (GuzzleException $e) {
            throw new RuntimeException('审核服务异常: ' . $this->formatGuzzleError($e), 0, $e);
        } catch (Exception $e) {
            throw new RuntimeException('审核服务异常: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPluginName(): string
    {
        return 'ContentAudit';
    }

    public function isAvailable(): bool
    {
        $this->ensureHttpClient();
        $apiToken = $this->getConfig('AUDIT_API_TOKEN', '');
        return isset($this->httpClient) && !empty($apiToken);
    }

    /**
     * 格式化连接错误.
     */
    protected function formatConnectionError(ConnectException $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Connection timed out')) {
            return '审核服务连接超时';
        }

        if (str_contains($message, 'Connection refused')) {
            return '审核服务连接被拒绝';
        }

        if (str_contains($message, 'Could not resolve host')) {
            return '无法解析审核服务地址';
        }

        return '审核服务连接失败: ' . $message;
    }

    /**
     * 格式化请求错误.
     */
    protected function formatRequestError(RequestException $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'timeout')) {
            return '审核服务响应超时';
        }

        $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
        if ($statusCode) {
            return "审核服务返回错误 (HTTP {$statusCode})";
        }

        return '审核服务请求失败: ' . $message;
    }

    /**
     * 格式化 Guzzle 错误.
     */
    protected function formatGuzzleError(GuzzleException $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'cURL error 28')) {
            return '审核服务响应超时';
        }

        if (str_contains($message, 'cURL error 7')) {
            return '无法连接到审核服务';
        }

        if (str_contains($message, 'cURL error 6')) {
            return '无法解析审核服务地址';
        }

        return '审核服务异常: ' . $message;
    }

    /**
     * 转换审核服务响应为插件内部格式.
     */
    protected function transformAuditResponse(array $response): array
    {
        $status = $response['status'] ?? 'unknown';
        $statusMap = [
            'passed' => 'pass',
            'warning' => 'pass',
            'rejected' => 'reject',
        ];

        return [
            'status' => $statusMap[$status] ?? 'pending',
            'score' => $response['score'] ?? 0,
            'reason' => $response['message'] ?? '',
            'details' => $response['details'] ?? [],
            'original_status' => $status,
        ];
    }
}
