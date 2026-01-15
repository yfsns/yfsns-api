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

namespace App\Modules\Location\Providers;

use App\Modules\Location\Services\LocationManager;
use App\Modules\Location\Services\LocationService;
use Illuminate\Support\ServiceProvider;

/**
 * 定位服务提供者.
 */
class LocationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 核心模块只注册自己的服务
        // 插件应该自己注册服务并实现核心模块的接口
        $this->app->singleton(LocationManager::class, function ($app) {
            return new LocationManager();
        });

        $this->app->singleton(LocationService::class, function ($app) {
            return new LocationService($app->make(LocationManager::class));
        });

        // 注册门面别名
        $this->app->alias(LocationService::class, 'location');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 加载数据库迁移文件
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        
        // 核心模块始终加载自己的路由
        // 如果插件已启用，插件的路由会覆盖核心路由
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
