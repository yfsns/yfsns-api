<?php

namespace Plugins\Location;

use App\Modules\PluginSystem\BasePlugin;

/**
 * 定位服务插件
 *
 * 提供完整的定位服务功能，包括：
 * - 逆地理编码（坐标转地址）
 * - 地理编码（地址转坐标）
 * - IP定位
 * - 距离计算
 *
 * 支持多种定位服务提供商：腾讯地图、高德地图、百度地图
 */
class Plugin extends BasePlugin
{
    /**
     * 初始化插件
     */
    protected function initialize(): void
    {
        $this->name = 'location';
        $this->version = '1.0.0';
        $this->description = '完整的定位服务系统，支持多种地图服务提供商';
        $this->author = 'YfSns';
        $this->requirements = [
            'php' => '>=8.1.0',
            'laravel' => '>=10.0.0',
        ];
    }

    /**
     * 插件启用时的处理
     */
    protected function onEnable(): void
    {
        parent::onEnable();

        // 注册服务提供者
        $this->registerServiceProvider();

        // 执行数据库迁移
        $this->runMigrations();

        // 加载API路由
        $this->loadRoutes();

        \Log::info('Location plugin enabled successfully');
    }

    /**
     * 注册服务提供者
     */
    protected function registerServiceProvider(): void
    {
        $providerClass = \Plugins\Location\Providers\LocationServiceProvider::class;

        if (class_exists($providerClass)) {
            app()->register($providerClass);
        } else {
            \Log::error('LocationServiceProvider class not found');
        }
    }

    /**
     * 加载路由
     */
    protected function loadRoutes(): void
    {
        $routeFile = __DIR__ . '/Routes/api.php';

        if (file_exists($routeFile)) {
            \Route::middleware('api')
                ->group($routeFile);
        }
    }

    /**
     * 执行数据库迁移
     */
    protected function runMigrations(): void
    {
        $migrationPath = __DIR__ . '/Database/Migrations';

        if (is_dir($migrationPath)) {
            // 这里可以执行插件特定的迁移
            // 目前定位插件不需要额外的数据库表
        }
    }
}