<?php

namespace Plugins\VoteSystem\Providers;

use Illuminate\Support\ServiceProvider;
use Plugins\VoteSystem\Services\VoteService;

class VoteSystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 注册投票服务
        $this->app->singleton(VoteService::class, function ($app) {
            return new VoteService();
        });

        // 合并配置文件
        $this->mergeConfigFrom(
            __DIR__.'/../config/config.php',
            'plugins.vote_system'
        );
    }

    public function boot(): void
    {
        // 加载迁移文件
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // 加载视图文件（如果有）
        if (is_dir(__DIR__.'/../resources/views')) {
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'vote-system');
        }

        // 加载语言文件（如果有）
        if (is_dir(__DIR__.'/../resources/lang')) {
            $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'vote-system');
        }

        // 发布配置文件
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('plugins/vote_system.php'),
        ], 'vote-system-config');

        // 发布迁移文件
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'vote-system-migrations');

        // 注册插件到系统（如果需要）
        $this->registerPlugin();
    }

    /**
     * 注册插件到系统
     */
    protected function registerPlugin(): void
    {
        // 这里可以注册插件的菜单、权限等
        // 具体实现取决于你的系统架构
    }

    public function provides(): array
    {
        return [
            VoteService::class,
        ];
    }
}
