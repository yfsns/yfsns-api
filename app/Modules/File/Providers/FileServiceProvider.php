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

namespace App\Modules\File\Providers;

use App\Modules\File\Services\LocalStorageService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FileServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册本地存储服务
        $this->app->singleton(LocalStorageService::class, function ($app) {
            return new LocalStorageService();
        });
    }

    /**
     * 引导服务
     */
    public function boot(): void
    {
        // 加载数据库迁移文件
        $this->loadMigrationsFrom(base_path('app/Modules/File/Database/Migrations'));

        // 加载路由 - 文件上传接口需要认证
        Route::prefix('api/v1')
            ->middleware('api')
            ->group(function (): void {
                $this->loadRoutesFrom(base_path('app/Modules/File/Routes/api.php'));
            });
    }

}
