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

namespace App\Modules\Notification\Providers;

use App\Modules\Notification\Channels\EmailChannel;
use App\Modules\Notification\Channels\SmsChannel;
use App\Modules\Notification\Contracts\NotificationStrategy;
use App\Modules\Notification\Events\CommentReplied;
use App\Modules\Notification\Events\PostCommented;
use App\Modules\Notification\Events\PostLiked;
use App\Modules\Notification\Events\UserLoggedIn;
use App\Modules\Notification\Events\UserMentioned;
use App\Modules\Notification\Listeners\SendNotifications;
use App\Modules\Notification\Services\EmailService;
use App\Modules\Notification\Services\NotificationDispatcher;
use App\Modules\Notification\Services\NotificationService;
use App\Modules\Notification\Strategies\CommentReplyNotificationStrategy;
use App\Modules\Notification\Strategies\LikeNotificationStrategy;
use App\Modules\Notification\Strategies\LoginNotificationStrategy;
use App\Modules\Notification\Strategies\MentionNotificationStrategy;
use App\Modules\Notification\Strategies\PostCommentNotificationStrategy;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册通知服务
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });

        // 注册邮件服务
        $this->app->singleton(EmailService::class, function ($app) {
            return new EmailService();
        });

        // 注册短信通道（直接调用SMS模块的服务）
        $this->app->singleton(SmsChannel::class, function ($app) {
            return new SmsChannel();
        });

        // 注册邮件通道
        $this->app->singleton(EmailChannel::class, function ($app) {
            return new EmailChannel($app->make(EmailService::class));
        });

        // 注册通知策略系统
        $this->registerNotificationStrategies();
    }

    /**
     * 注册通知策略系统
     */
    protected function registerNotificationStrategies(): void
    {
        // 注册通知分发器
        $this->app->singleton(NotificationDispatcher::class, function ($app) {
            return new NotificationDispatcher([
                $app->make(MentionNotificationStrategy::class),
                $app->make(LoginNotificationStrategy::class),
                $app->make(CommentReplyNotificationStrategy::class),
                $app->make(PostCommentNotificationStrategy::class),
                $app->make(LikeNotificationStrategy::class),
            ]);
        });

        // 注册各个策略类
        $this->app->singleton(MentionNotificationStrategy::class);
        $this->app->singleton(LoginNotificationStrategy::class);
        $this->app->singleton(CommentReplyNotificationStrategy::class);
        $this->app->singleton(PostCommentNotificationStrategy::class);
        $this->app->singleton(LikeNotificationStrategy::class);
    }

    /**
     * 引导服务
     */
    public function boot(): void
    {
        // 注册通知通道
        $this->app->make(ChannelManager::class)->extend('sms', function ($app) {
            return $app->make(SmsChannel::class);
        });

        $this->app->make(ChannelManager::class)->extend('email', function ($app) {
            return $app->make(EmailChannel::class);
        });

        // 注册事件监听器
        $this->registerEventListeners();

        // 加载API路由（必须包含api中间件组）
        Route::prefix('api/v1')
            ->middleware('api')  // 添加api中间件组
            ->group(function (): void {
                $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
            });

        // 加载管理员路由 - 只添加基础 api 中间件，认证中间件在路由文件中定义
        Route::prefix('api/admin')
            ->middleware(['api'])
            ->group(function (): void {
                $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');
            });

        // 加载迁移
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
    

    /**
     * 注册事件监听器
     */
    protected function registerEventListeners(): void
    {
        // 注册通知事件监听器
        Event::listen([
            UserMentioned::class,
            UserLoggedIn::class,
            CommentReplied::class,
            PostCommented::class,
            PostLiked::class,
        ], SendNotifications::class);
    }
}
