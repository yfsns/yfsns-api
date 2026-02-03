<?php

namespace Plugins\ContentAudit;

use App\Modules\PluginSystem\BaseDeclarativePlugin;
use App\Modules\PluginSystem\Contracts\PluginInterface;
use Exception;

use function in_array;
use function is_string;

use Illuminate\Support\Facades\Log;

class Plugin extends BaseDeclarativePlugin
{
    public function __construct()
    {
        $this->name = 'contentaudit';
        $this->version = '1.0.0';
        $this->description = '内容审核插件，对接审核服务API，进行AI审核文章和动态';
        $this->author = 'yfsns';
    }

    protected function onInstall(): void
    {
        // 插件安装时的逻辑
        // 注册插件配置项
        $this->registerPluginConfigs();
    }

    /**
     * 注册插件配置项
     */
    protected function registerPluginConfigs(): void
    {
        // 插件系统已禁用，跳过配置注册
        // 配置通过 config.json 文件自动注册
    }

    protected function onUninstall(): void
    {
        // 插件卸载时的逻辑
        // 可以在这里清理数据、删除表等
    }

    public function register(): void
    {
        // 注册审核服务
        $this->app->singleton(
            Services\AuditService::class,
            fn () => new Services\AuditService(
                app(\App\Modules\PluginSystem\Services\PluginConfigManagerService::class)
            )
        );
    }

    /**
     * 引导服务
     */
    public function boot(): void
    {
        // 插件已通过PluginSystemServiceProvider自动注册
        // 这里只处理启用状态
        if ($this->isEnabled()) {
            $this->onEnable();
        }
    }

    /**
     * 插件启用时的处理
     */
    protected function onEnable(): void
    {
        // 注册事件监听器
        $this->registerEventListeners();

        Log::info('ContentAudit 插件已启用');
    }



    /**
     * 注册事件监听器
     * 只在插件启用时调用，确保主程序与插件完全解耦.
     */
    protected function registerEventListeners(): void
    {
        // 监听内容待审核事件，当有新内容待审核时，分发队列任务进行异步审核
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Review\Events\ContentPendingAudit::class,
            Listeners\ContentPendingAuditListener::class
        );

        Log::info('ContentAudit 插件事件监听器已注册', [
            'event' => \App\Modules\Review\Events\ContentPendingAudit::class,
            'listener' => Listeners\ContentPendingAuditListener::class,
        ]);
    }

    /**
     * 初始化插件
     */
    protected function initialize()
    {
        // 插件初始化逻辑
        $this->name = 'contentaudit';
        $this->version = '1.0.0';
        $this->description = '内容审核插件，对接审核服务API，进行AI审核文章和动态';
        $this->author = 'yfsns';
    }

}
