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

namespace App\Modules\Review\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ReviewServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        \App\Modules\Review\Events\ContentPendingAudit::class => [
            \App\Modules\Review\Listeners\ContentPendingAuditListener::class,
        ],
    ];

    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册审核服务
        $this->app->singleton(\App\Modules\Review\Services\ReviewService::class);

        // 注册审核决策服务
        $this->app->singleton(\App\Modules\Review\Services\ReviewDecisionService::class);
    }

    /**
     * 引导服务
     */
    public function boot(): void
    {
        // 加载数据库迁移文件
        $this->loadMigrationsFrom(base_path('app/Modules/Review/Database/Migrations'));

        // 加载后台路由 - 只添加基础 api 中间件，认证中间件在路由文件中定义
        Route::prefix('api/admin')
            ->middleware(['api'])
            ->group(function (): void {
                $this->loadRoutesFrom(base_path('app/Modules/Review/Routes/admin.php'));
            });
    }
}
