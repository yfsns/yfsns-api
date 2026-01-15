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

namespace App\Modules\Sms\Providers;

use App\Modules\Sms\Channels\BuiltIn\AliyunChannel;
use App\Modules\Sms\Channels\BuiltIn\TencentChannel;
use App\Modules\Sms\Channels\Plugin\PluginChannelBridge;
use App\Modules\Sms\Channels\Registry\SmsChannelRegistry;
use App\Modules\Sms\Channels\Registry\SmsChannelRegistryInterface;
use App\Modules\Sms\Config\SmsConfigManager;
use App\Modules\Sms\Services\SmsService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册通道注册器
        $this->app->singleton(SmsChannelRegistryInterface::class, function ($app) {
            $registry = new SmsChannelRegistry();

            // 注册内置通道
            $registry->registerChannel('aliyun', AliyunChannel::class);
            $registry->registerChannel('tencent', TencentChannel::class);

            return $registry;
        });

        // 注册配置管理器
        $this->app->singleton(SmsConfigManager::class, function ($app) {
            return new SmsConfigManager($app->make(SmsChannelRegistryInterface::class));
        });

        // 注册插件通道桥接器
        $this->app->singleton(PluginChannelBridge::class, function ($app) {
            return new PluginChannelBridge($app->make(SmsChannelRegistryInterface::class));
        });

        // 注册短信服务
        $this->app->singleton(SmsService::class, function ($app) {
            return new SmsService(
                $app->make(SmsChannelRegistryInterface::class),
                $app->make(SmsConfigManager::class)
            );
        });

        // 保持向后兼容的别名
        $this->app->alias(SmsService::class, 'sms.service');
    }

    /**
     * 引导服务
     */
    public function boot(): void
    {
        // 加载用户端 API 路由
        Route::prefix('api/v1')
            ->middleware(['api'])
            ->group(function (): void {
                $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
            });

        // 加载后台管理路由 - 只添加基础 api 中间件，认证中间件在路由文件中定义
        Route::prefix('api/admin')
            ->middleware(['api'])
            ->group(function (): void {
                $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');
            });

        // 加载迁移
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

}
