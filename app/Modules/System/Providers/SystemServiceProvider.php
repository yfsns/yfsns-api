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

namespace App\Modules\System\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SystemServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册系统核心服务
        $this->registerSystemServices();
    }

    /**
     * 引导服务
     */
    public function boot(): void
    {
        // 加载数据库迁移文件
        $this->loadMigrationsFrom(base_path('app/Modules/System/Database/Migrations'));

        // 加载路由
        $this->loadRoutes();
    }

    /**
     * 注册系统核心服务
     */
    protected function registerSystemServices(): void
    {
        // 注册系统配置服务
        $this->app->singleton(\App\Modules\System\Services\ConfigService::class, function ($app) {
            return new \App\Modules\System\Services\ConfigService();
        });

        // 注册密码验证服务
        $this->app->singleton(\App\Modules\System\Services\PasswordValidationService::class, function ($app) {
            return new \App\Modules\System\Services\PasswordValidationService();
        });

        // 注册缓存清除服务
        $this->app->singleton(\App\Modules\System\Services\CacheClearService::class, function ($app) {
            return new \App\Modules\System\Services\CacheClearService();
        });

        // 注册网站配置服务
        $this->app->singleton(\App\Modules\System\Services\WebsiteConfigService::class, function ($app) {
            return new \App\Modules\System\Services\WebsiteConfigService(
                $app->make(\App\Modules\System\Services\CacheClearService::class)
            );
        });

        // 注册操作日志服务
        $this->app->singleton(\App\Modules\System\Services\OperationLogService::class, function ($app) {
            return new \App\Modules\System\Services\OperationLogService();
        });

        // 注册系统信息服务
        $this->app->singleton(\App\Modules\System\Services\SysinfoService::class, function ($app) {
            return new \App\Modules\System\Services\SysinfoService();
        });

        // 注册内容审核配置服务
        $this->app->singleton(\App\Modules\System\Services\ContentReviewConfigService::class, function ($app) {
            return new \App\Modules\System\Services\ContentReviewConfigService();
        });
    }

    /**
     * 加载路由
     */
    protected function loadRoutes(): void
    {
        // API路由
        Route::middleware('api')
            ->prefix('api/v1')
            ->group(function (): void {
                $this->loadRoutesFrom(base_path('app/Modules/System/Routes/api.php'));
            });

        // 管理后台路由 - 只添加基础 api 中间件，认证中间件在路由文件中定义
        Route::prefix('api/admin')
            ->middleware(['api'])
            ->group(function (): void {
                $this->loadRoutesFrom(base_path('app/Modules/System/Routes/admin.php'));
            });
    }
}
