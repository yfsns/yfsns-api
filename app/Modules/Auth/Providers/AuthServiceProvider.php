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

namespace App\Modules\Auth\Providers;

use App\Http\Services\IpLocationService;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Services\AdminAuthService;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\VerificationService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        $this->app->singleton(AuthService::class);
        $this->app->singleton(AdminAuthService::class);
        $this->app->singleton(VerificationService::class);
        $this->app->singleton(IpLocationService::class);

        // 注册模块配置
        $this->registerModuleConfig();
    }

    /**
     * 引导服务
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Log::info('AuthServiceProvider booting...');

        // 加载路由
        $this->loadRoutes();
    }

    /**
     * 注册模块配置
     */
    protected function registerModuleConfig(): void
    {
        $configPath = base_path('app/Modules/Auth/Config');

        if (is_dir($configPath)) {
            $files = glob($configPath . '/*.php');

            foreach ($files as $file) {
                $configName = basename($file, '.php');
                $configData = require $file;

                // 合并到全局配置
                config(['auth.' . $configName => $configData]);

                // 同时设置简短的别名
                if ($configName === 'password') {
                    config(['password' => $configData]);
                }
            }
        }
    }

    /**
     * 加载路由.
     */
    protected function loadRoutes(): void
    {
        // 前台 API 路由
        Route::prefix('api/v1')
            ->middleware(['api'])
            ->group(function (): void {
                $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
            });


        // 后台 API 路由 - 受保护路由（需要管理员认证）
        Route::prefix('api/admin')
            ->middleware(['api'])
            ->group(function (): void {
                // 加载需要认证的路由
                $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');
            });
    }
}
