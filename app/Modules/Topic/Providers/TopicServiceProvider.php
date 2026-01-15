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

namespace App\Modules\Topic\Providers;

use App\Modules\Topic\Events\TopicsUpdated;
use App\Modules\Topic\Listeners\TopicsUpdatedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TopicServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册服务
    }

    /**
     * 引导服务
     */
    public function boot(): void
    {
        // 加载数据库迁移文件
        $this->loadMigrationsFrom(base_path('app/Modules/Topic/Database/Migrations'));

        Route::middleware('api')
            ->prefix('api/v1')
            ->group(function (): void {
                $this->loadRoutesFrom(base_path('app/Modules/Topic/Routes/api.php'));
            });

        // 后台 API 路由 - 只添加基础 api 中间件，认证中间件在路由文件中定义
        Route::prefix('api/admin')
            ->middleware(['api'])
            ->group(function (): void {
                $this->loadRoutesFrom(base_path('app/Modules/Topic/Routes/admin.php'));
            });

        // 注册事件监听器
        $this->registerEventListeners();
    }

    /**
     * 注册事件监听器
     */
    protected function registerEventListeners(): void
    {
        // 监听通用话题更新事件
        Event::listen(
            TopicsUpdated::class,
            [TopicsUpdatedListener::class, 'handle']
        );
    }
}
