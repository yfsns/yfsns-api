<?php

namespace App\Providers;

use App\Repositories\ConfigRepository;
use Illuminate\Support\ServiceProvider;

/**
 * 配置服务提供者
 *
 * 注册声明式配置系统
 */
class ConfigServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册配置仓库
        $this->app->singleton(ConfigRepository::class, function ($app) {
            return new ConfigRepository();
        });

        // 注册便捷访问方法
        $this->app->singleton('config.repo', function ($app) {
            return $app->make(ConfigRepository::class);
        });
    }

    /**
     * 引导服务
     */
    public function boot(): void
    {
        // 延迟到应用完全启动后再解析配置
        // 这样可以避免在配置加载阶段使用数据库
        $this->app->booted(function () {
            $this->resolveDeclarativeConfigs();
        });

        // 可以在这里添加配置迁移
        // $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    /**
     * 自动解析声明式配置
     */
    protected function resolveDeclarativeConfigs(): void
    {
        // 检查应用是否已完全启动
        if (!$this->app->isBooted()) {
            return;
        }

        $configRepository = app(ConfigRepository::class);

        // 检查数据库表是否存在
        if (!$this->hasSystemConfigsTable()) {
            \Log::info('system_configs table not found, skipping declarative config resolution');
            return;
        }

        // 所有配置现在都由各自的模块管理，不再需要全局处理
    }

    /**
     * 检查system_configs表是否存在
     */
    protected function hasSystemConfigsTable(): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable('system_configs');
        } catch (\Exception $e) {
            // 数据库未配置或连接失败时，返回 false，避免应用启动失败
            // 这在安装阶段特别重要，因为此时数据库可能还未配置
            \Log::debug('Database connection failed when checking system_configs table: ' . $e->getMessage());
            return false;
        }
    }
}
