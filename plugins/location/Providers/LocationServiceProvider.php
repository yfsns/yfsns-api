<?php

namespace Plugins\Location\Providers;

use App\Modules\PluginSystem\Services\PluginConfigManagerService;
use Illuminate\Support\ServiceProvider;
use Plugins\Location\Services\LocationManager;
use Plugins\Location\Services\LocationService;

/**
 * 定位服务插件提供者
 */
class LocationServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 插件必须在核心模块之后注册，以确保能正确替换绑定
        // 使用 boot() 方法而不是 register() 来延迟执行，确保所有配置都已加载
    }

    public function boot(): void
    {
        // 在 boot() 中注册插件服务，确保在所有核心服务注册完成后执行
        $this->registerPluginServices();
    }

    protected function registerPluginServices(): void
    {
        try {
            $pluginConfigManager = app(\App\Modules\PluginSystem\Services\PluginConfigManagerService::class);
            $pluginName = 'Location';

            $tencentKey = $pluginConfigManager->getPluginConfigValue($pluginName, 'TENCENT_MAP_KEY', '');
            $tencentEnabled = $pluginConfigManager->getPluginConfigValue($pluginName, 'TENCENT_MAP_ENABLED', true);

            // 确保配置值正确转换
            $tencentEnabled = filter_var($tencentEnabled, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;

            \Log::debug('LocationServiceProvider: Loading config', [
                'pluginName' => $pluginName,
                'tencentKey' => $tencentKey ? 'exists' : 'empty',
                'tencentEnabled' => $tencentEnabled,
            ]);

            $driversConfig = [
                'tencent' => [
                    'driver' => \Plugins\Location\Drivers\TencentDriver::class,
                    'enabled' => $tencentEnabled,
                    'api_key' => (string) $tencentKey,
                    'timeout' => (int) $pluginConfigManager->getPluginConfigValue($pluginName, 'TENCENT_MAP_TIMEOUT', 5),
                ],
                'amap' => [
                    'driver' => \Plugins\Location\Drivers\AmapDriver::class,
                    'enabled' => filter_var($pluginConfigManager->getPluginConfigValue($pluginName, 'AMAP_ENABLED', false), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
                    'api_key' => (string) $pluginConfigManager->getPluginConfigValue($pluginName, 'AMAP_KEY', ''),
                    'api_secret' => (string) $pluginConfigManager->getPluginConfigValue($pluginName, 'AMAP_SECRET', ''),
                    'timeout' => (int) $pluginConfigManager->getPluginConfigValue($pluginName, 'AMAP_TIMEOUT', 5),
                ],
                'baidu' => [
                    'driver' => \Plugins\Location\Drivers\BaiduDriver::class,
                    'enabled' => filter_var($pluginConfigManager->getPluginConfigValue($pluginName, 'BAIDU_MAP_ENABLED', false), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
                    'api_key' => (string) $pluginConfigManager->getPluginConfigValue($pluginName, 'BAIDU_MAP_AK', ''),
                    'timeout' => (int) $pluginConfigManager->getPluginConfigValue($pluginName, 'BAIDU_MAP_TIMEOUT', 5),
                ],
                'mock' => [
                    'driver' => \Plugins\Location\Drivers\MockDriver::class,
                    'enabled' => true, // Mock驱动始终可用
                    'api_key' => 'mock',
                    'timeout' => 1,
                ],
            ];

            $defaultDriver = (string) $pluginConfigManager->getPluginConfigValue($pluginName, 'LOCATION_DEFAULT_DRIVER', 'tencent');
            $cacheEnabled = filter_var($pluginConfigManager->getPluginConfigValue($pluginName, 'LOCATION_CACHE_ENABLED', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
            $cacheTtlMinutes = (int) $pluginConfigManager->getPluginConfigValue($pluginName, 'LOCATION_CACHE_TTL', 1440);
            $cacheTtl = $cacheTtlMinutes * 60; // 转换为秒
            $enableFallback = filter_var($pluginConfigManager->getPluginConfigValue($pluginName, 'LOCATION_ENABLE_FALLBACK', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
            $defaultCoordType = (string) $pluginConfigManager->getPluginConfigValue($pluginName, 'LOCATION_DEFAULT_COORD_TYPE', 'gcj02');
            $logEnabled = filter_var($pluginConfigManager->getPluginConfigValue($pluginName, 'LOCATION_LOG_ENABLED', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
            $logChannel = (string) $pluginConfigManager->getPluginConfigValue($pluginName, 'LOCATION_LOG_CHANNEL', 'daily');

            // 注册插件的 LocationManager（私有实现）
            $this->app->singleton(\Plugins\Location\Services\LocationManager::class, function () use ($driversConfig, $defaultDriver, $cacheEnabled, $cacheTtl, $enableFallback, $defaultCoordType, $logEnabled, $logChannel) {
                return new \Plugins\Location\Services\LocationManager(
                    $driversConfig,
                    $defaultDriver,
                    $cacheEnabled,
                    $cacheTtl,
                    $enableFallback,
                    $defaultCoordType,
                    $logEnabled,
                    $logChannel
                );
            });

            // 注册插件的 LocationService（私有实现）
            $this->app->singleton(\Plugins\Location\Services\LocationService::class, function ($app) {
                return new \Plugins\Location\Services\LocationService($app->make(\Plugins\Location\Services\LocationManager::class));
            });

            // 关键：替换核心模块的 LocationManager 到插件的实现
            $this->app->singleton(\App\Modules\Location\Services\LocationManager::class, function ($app) {
                return $app->make(\Plugins\Location\Services\LocationManager::class);
            });

            // 关键：替换核心模块的 LocationService 到插件的实现
            $this->app->singleton(\App\Modules\Location\Services\LocationService::class, function ($app) {
                return $app->make(\Plugins\Location\Services\LocationService::class);
            });

            // 注册门面别名
            $this->app->alias(\App\Modules\Location\Services\LocationService::class, 'location');

            \Log::info('Location plugin services registered successfully');

        } catch (\Exception $e) {
            \Log::error('LocationServiceProvider: Failed to register plugin services', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * 加载请求验证类
     */
    protected function loadRequestClasses(): void
    {
        // 确保请求类能被正确加载
        $requestClasses = [
            \Plugins\Location\Http\Requests\CalculateDistanceRequest::class,
            \Plugins\Location\Http\Requests\GeocodeRequest::class,
            \Plugins\Location\Http\Requests\GetLocationByIpRequest::class,
            \Plugins\Location\Http\Requests\ReverseGeocodeRequest::class,
        ];

        foreach ($requestClasses as $requestClass) {
            if (class_exists($requestClass)) {
                // 类已存在，不需要额外操作
            }
        }
    }

    /**
     * 获取提供者提供的服务
     */
    public function provides(): array
    {
        return [
            LocationManager::class,
            LocationService::class,
        ];
    }
}
