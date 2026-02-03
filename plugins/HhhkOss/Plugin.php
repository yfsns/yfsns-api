<?php

namespace Plugins\HhhkOss;

use App\Modules\PluginSystem\BasePlugin;
use App\Modules\PluginSystem\Contracts\PluginInterface;

class Plugin extends BasePlugin
{
    protected $name = 'hhhkoss';
    protected $version = '1.0.0';
    protected $description = '阿里云OSS文件存储插件';
    protected $author = 'hhhk';
    protected $dependencies = [];
    protected $requirements = [
        'php' => '>=8.1',
        'laravel' => '>=10.0'
    ];

    protected function initialize(): void
    {
        // 初始化插件的基本信息
        // 这个方法在插件注册时会被调用
    }

    protected function onEnable(): void
    {
        parent::onEnable();

        // 注册OSS服务
        $this->registerOssService();

        // 加载路由
        $this->loadRoutes();

        // 注册文件系统驱动
        $this->registerFilesystemDriver();
    }

    protected function onDisable(): void
    {
        parent::onDisable();

        // 清理OSS相关服务
        $this->cleanupOssService();
    }

    protected function onInstall(): void
    {
        parent::onInstall();

        // 初始化配置（如果还没有config.values.json文件）
        $this->initializeConfig();
    }

    protected function onUninstall(): void
    {
        parent::onUninstall();

        // 删除配置文件
        $this->cleanupConfig();
    }

    protected function registerOssService(): void
    {
        app()->singleton(
            \Plugins\HhhkOss\Services\OssService::class,
            fn () => new \Plugins\HhhkOss\Services\OssService()
        );
    }

    protected function loadRoutes(): void
    {
        if (file_exists(__DIR__ . '/routes/api.php')) {
            require __DIR__ . '/routes/api.php';
        }
    }

    protected function registerFilesystemDriver(): void
    {
        // 注册阿里云OSS文件系统驱动
        \Storage::extend('oss', function ($app, $config) {
            return new \Plugins\HhhkOss\Services\OssAdapter($config);
        });
    }

    protected function cleanupOssService(): void
    {
        // 清理OSS服务缓存等
        \Cache::forget('hhhk_oss_config');
    }

    /**
     * 初始化配置（如果还没有config.values.json文件）.
     */
    protected function initializeConfig(): void
    {
        $valuesPath = base_path("plugins/{$this->name}/config.values.json");

        if (!file_exists($valuesPath)) {
            // 从schema中获取默认值
            $schema = $this->getConfigSchema();
            $defaultValues = $schema['values'] ?? [];

            // 保存默认配置
            $this->setConfigValues($defaultValues);
        }
    }

    /**
     * 清理配置.
     */
    protected function cleanupConfig(): void
    {
        $valuesPath = base_path("plugins/{$this->name}/config.values.json");
        if (file_exists($valuesPath)) {
            unlink($valuesPath);
        }

        // 清除缓存
        \Cache::forget('hhhk_oss_config');
    }
}
