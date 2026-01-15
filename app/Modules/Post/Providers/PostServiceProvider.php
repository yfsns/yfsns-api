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

namespace App\Modules\Post\Providers;

use App\Modules\Post\Models\Post;
use App\Modules\Post\Policies\PostPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PostServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册动态服务
        $this->app->singleton(\App\Modules\Post\Services\PostService::class);

        // 注册管理员动态服务
        $this->app->singleton(\App\Modules\Post\Services\AdminPostService::class);
    }

    /**
     * 引导服务
     */
    public function boot(): void
    {
        // 注册权限策略
        Gate::policy(Post::class, PostPolicy::class);

        // 加载路由
        $this->loadRoutes();

        // 加载视图
        $this->loadViewsFrom(base_path('app/Modules/Post/resources/views'), 'post');

        // 加载迁移
        $this->loadMigrationsFrom(base_path('app/Modules/Post/Database/Migrations'));

        // 注意：Post Observer 现在由 ContentAudit 插件接管，无需在主应用中注册
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
                $this->loadRoutesFrom(base_path('app/Modules/Post/Routes/api.php'));
            });

        // 后台 API 路由
        Route::prefix('api/admin')
            ->middleware(['api'])
            ->group(function (): void {
                $this->loadRoutesFrom(base_path('app/Modules/Post/Routes/admin.php'));
            });
    }
}
