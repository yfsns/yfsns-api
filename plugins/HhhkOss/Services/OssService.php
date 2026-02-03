<?php

namespace Plugins\HhhkOss\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class OssService
{
    protected array $config;

    public function __construct()
    {
        $this->config = $this->loadPluginConfig();
    }

    /**
     * 从插件系统加载OSS配置
     */
    protected function loadPluginConfig(): array
    {
        // 尝试从缓存中获取
        $cached = \Cache::get('hhhk_oss_config');
        if ($cached) {
            return $cached;
        }

        // 使用 PluginConfigManagerService 获取配置（符合插件系统规范）
        try {
            $configManager = app(\App\Modules\PluginSystem\Services\PluginConfigManagerService::class);
            $pluginName = 'HhhkOss';

            $config = [
                'access_key_id' => $configManager->getPluginConfigValue($pluginName, 'OSS_ACCESS_KEY_ID', ''),
                'access_key_secret' => $configManager->getPluginConfigValue($pluginName, 'OSS_ACCESS_KEY_SECRET', ''),
                'bucket' => $configManager->getPluginConfigValue($pluginName, 'OSS_BUCKET', ''),
                'endpoint' => $configManager->getPluginConfigValue($pluginName, 'OSS_ENDPOINT', 'oss-cn-hangzhou.aliyuncs.com'),
                'region' => $configManager->getPluginConfigValue($pluginName, 'OSS_REGION', 'cn-hangzhou'),
                'is_cname' => (bool) $configManager->getPluginConfigValue($pluginName, 'OSS_IS_CNAME', false),
                'ssl' => (bool) $configManager->getPluginConfigValue($pluginName, 'OSS_SSL', true),
                'timeout' => (int) $configManager->getPluginConfigValue($pluginName, 'OSS_TIMEOUT', 60),
                'cdn_domain' => $configManager->getPluginConfigValue($pluginName, 'OSS_CDN_DOMAIN', ''),
                'cdn_enabled' => (bool) $configManager->getPluginConfigValue($pluginName, 'OSS_CDN_ENABLED', false),
            ];
        } catch (\Exception $e) {
            // fallback：如果插件系统不可用，直接读取文件
            Log::warning('HhhkOss: 无法从配置管理器加载配置，使用文件fallback: ' . $e->getMessage());
            $valuesPath = base_path('plugins/HhhkOss/config.values.json');
            if (File::exists($valuesPath)) {
                $content = File::get($valuesPath);
                $fileConfig = json_decode($content, true) ?? [];
                // 转换配置键名为小写加下划线格式
                $config = [
                    'access_key_id' => $fileConfig['OSS_ACCESS_KEY_ID'] ?? '',
                    'access_key_secret' => $fileConfig['OSS_ACCESS_KEY_SECRET'] ?? '',
                    'bucket' => $fileConfig['OSS_BUCKET'] ?? '',
                    'endpoint' => $fileConfig['OSS_ENDPOINT'] ?? 'oss-cn-hangzhou.aliyuncs.com',
                    'region' => $fileConfig['OSS_REGION'] ?? 'cn-hangzhou',
                    'is_cname' => (bool) ($fileConfig['OSS_IS_CNAME'] ?? false),
                    'ssl' => (bool) ($fileConfig['OSS_SSL'] ?? true),
                    'timeout' => (int) ($fileConfig['OSS_TIMEOUT'] ?? 60),
                    'cdn_domain' => $fileConfig['OSS_CDN_DOMAIN'] ?? '',
                    'cdn_enabled' => (bool) ($fileConfig['OSS_CDN_ENABLED'] ?? false),
                ];
            } else {
                $config = [];
            }
        }

        // 缓存配置（10分钟）
        \Cache::put('hhhk_oss_config', $config, 600);

        return $config;
    }

    /**
     * 上传文件到OSS
     */
    public function uploadFile(string $filePath, string $ossKey): bool
    {
        try {
            // 这里是简单的示例实现
            // 实际项目中需要使用阿里云OSS SDK

            Log::info('HhhkOss: 模拟上传文件', [
                'file_path' => $filePath,
                'oss_key' => $ossKey
            ]);

            // 模拟上传成功
            return true;

        } catch (\Exception $e) {
            Log::error('HhhkOss: 文件上传失败', [
                'file_path' => $filePath,
                'oss_key' => $ossKey,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 删除OSS文件
     */
    public function deleteFile(string $ossKey): bool
    {
        try {
            Log::info('HhhkOss: 模拟删除文件', [
                'oss_key' => $ossKey
            ]);

            // 模拟删除成功
            return true;

        } catch (\Exception $e) {
            Log::error('HhhkOss: 文件删除失败', [
                'oss_key' => $ossKey,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 获取文件URL
     */
    public function getFileUrl(string $ossKey, int $expire = 3600): string
    {
        // 简单的URL生成示例
        $bucket = $this->config['bucket'] ?? '';
        $endpoint = $this->config['endpoint'] ?? '';

        return "https://{$bucket}.{$endpoint}/{$ossKey}";
    }

    /**
     * 检查OSS配置是否完整
     */
    public function isConfigured(): bool
    {
        $required = ['access_key_id', 'access_key_secret', 'bucket', 'endpoint'];
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取OSS配置信息
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
