<?php

namespace App\Modules\PluginSystem\Providers;

use App\Modules\PluginSystem\Contracts\PluginInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PluginSystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 注册插件验证器
        $this->app->singleton(\App\Modules\PluginSystem\Services\Checks\PluginValidatorService::class, function ($app) {
            return new \App\Modules\PluginSystem\Services\Checks\PluginValidatorService();
        });

        // 注册插件安全检查服务
        $this->app->singleton(\App\Modules\PluginSystem\Contracts\PluginSecurityCheckerInterface::class, function ($app) {
            return new \App\Modules\PluginSystem\Services\Checks\PluginSecurityCheckerService(
                $app->make(\App\Modules\PluginSystem\Services\Checks\PluginSyntaxValidatorService::class)
            );
        });

        // 注册插件安装管理器
        $this->app->singleton(\App\Modules\PluginSystem\Services\PluginInstallerService::class, function ($app) {
            return new \App\Modules\PluginSystem\Services\PluginInstallerService(
                $app->make(\App\Modules\PluginSystem\Services\PluginConfigManagerService::class)
            );
        });

        // 注册插件发现服务
        $this->app->singleton(\App\Modules\PluginSystem\Services\PluginDiscoveryService::class, function ($app) {
            return new \App\Modules\PluginSystem\Services\PluginDiscoveryService();
        });

        // 注册插件管理器
        $this->app->singleton(\App\Modules\PluginSystem\Services\PluginManagerService::class, function ($app) {
            return new \App\Modules\PluginSystem\Services\PluginManagerService(
                $app->make(\App\Modules\PluginSystem\Services\PluginListService::class),
                $app->make(\App\Modules\PluginSystem\Contracts\PluginSecurityCheckerInterface::class)
            );
        });

        // 注册插件管理器别名
        $this->app->alias(\App\Modules\PluginSystem\Services\PluginManagerService::class, 'plugin.manager');

    }

    public function boot(): void
    {
        // 加载插件系统相关文件
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // 加载路由
        $this->loadRoutesFrom(__DIR__.'/../routes.php');

        // 加载已启用插件的提供者和路由
        $this->loadEnabledPluginProviders();
        $this->loadEnabledPluginRoutes();

        // 轻量化设计：移除复杂的Artisan命令

        // 在应用完全启动后进行轻量化插件发现（只执行一次）
        static $bootedExecuted = false;
        $this->app->booted(function () use (&$bootedExecuted) {
            if (!$bootedExecuted) {
                $bootedExecuted = true;
                $this->discoverPluginsLightweight();
            }
        });
    }

    /**
     * 加载已启用插件的路由
     */
    protected function loadEnabledPluginProviders(): void
    {
        try {
            $enabledPlugins = \App\Modules\PluginSystem\Models\PluginInstallation::enabled()
                ->installed()
                ->get();

            foreach ($enabledPlugins as $installation) {
                $pluginName = $installation->plugin_name;
                $pluginPath = base_path("plugins/{$pluginName}");

                if (!is_dir($pluginPath)) {
                    continue;
                }

                // 加载插件的 ServiceProvider
                $providerFile = $pluginPath . '/Providers/' . $pluginName . 'ServiceProvider.php';
                if (file_exists($providerFile)) {
                    try {
                        $providerClass = "\\Plugins\\{$pluginName}\\Providers\\{$pluginName}ServiceProvider";
                        if (class_exists($providerClass)) {
                            $this->app->register($providerClass);
                            \Log::debug("Loaded plugin provider: {$providerClass}");
                        } else {
                            \Log::warning("Plugin provider class not found: {$providerClass}");
                        }
                    } catch (\Exception $e) {
                        \Log::error("Failed to load plugin provider: {$providerFile}", ['error' => $e->getMessage()]);
                    }
                }
            }
        } catch (\Exception $e) {
            // 如果数据库表不存在或查询失败，记录错误但不中断应用启动
            \Log::warning('Failed to load enabled plugin providers', ['error' => $e->getMessage()]);
        }
    }

    protected function loadEnabledPluginRoutes(): void
    {
        try {
            // 获取所有已启用且已安装的插件
            $enabledPlugins = \App\Modules\PluginSystem\Models\PluginInstallation::enabled()
                ->installed()
                ->get();

            foreach ($enabledPlugins as $installation) {
                $pluginName = $installation->plugin_name;
                $pluginPath = base_path("plugins/{$pluginName}");

                if (!is_dir($pluginPath)) {
                    continue;
                }

                // 加载 Routes/api.php
                $routeFile = $pluginPath . '/Routes/api.php';
                if (file_exists($routeFile)) {
                    try {
                        Route::middleware('api')->group($routeFile);
                        \Log::debug("Loaded plugin routes: {$routeFile}");
                    } catch (\Exception $e) {
                        \Log::error("Failed to load plugin routes: {$routeFile}", ['error' => $e->getMessage()]);
                    }
                }
            }
        } catch (\Exception $e) {
            // 如果数据库表不存在或查询失败，记录错误但不中断应用启动
            \Log::warning('Failed to load enabled plugin routes', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 轻量化插件发现
     * 只记录插件数量，不进行复杂操作
     */
    protected function discoverPluginsLightweight(): void
    {
        \Log::info('PluginSystemServiceProvider: Starting lightweight plugin discovery');

        // 扫描plugins目录
        $pluginPath = base_path('plugins');

        if (!is_dir($pluginPath)) {
            \Log::info('PluginSystemServiceProvider: Plugins directory not found: ' . $pluginPath);
            return;
        }

        $pluginDirs = glob("$pluginPath/*", GLOB_ONLYDIR);
        $pluginCount = 0;

        // 简单统计插件数量
        foreach ($pluginDirs as $pluginDir) {
            $pluginFile = $pluginDir . '/Plugin.php';
            if (file_exists($pluginFile)) {
                $pluginCount++;
            }
        }

        \Log::info('PluginSystemServiceProvider: Found ' . $pluginCount . ' plugins');
        \Log::info('PluginSystemServiceProvider: Lightweight discovery completed');
    }


}
