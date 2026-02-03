<?php

namespace Plugins\ExamplePlugin;

use App\Modules\PluginSystem\BasePlugin;
use App\Modules\PluginSystem\Contracts\PluginInterface;

/**
 * 示例插件 - 演示如何正确开发安全合规的插件
 *
 * 这个插件展示了：
 * 1. 正确的插件结构
 * 2. 安全的路由注册
 * 3. 正确的权限定义
 * 4. 依赖关系声明
 */
class Plugin extends BasePlugin
{
    /**
     * 初始化插件配置
     */
    protected function initialize(): void
    {
        $this->name = 'exampleplugin';
        $this->version = '1.0.0';
        $this->description = '示例插件，演示如何正确开发安全合规的插件';
        $this->author = 'YFSNS Team';

        // 声明依赖关系
        $this->dependencies = [];

        // 定义插件权限
        $this->permissions = [
            'example.view' => '查看示例内容',
            'example.create' => '创建示例内容',
            'example.edit' => '编辑示例内容',
            'example.delete' => '删除示例内容',
            'example.admin' => '管理示例插件',
        ];

        $this->enabled = false;
    }

    /**
     * 获取插件信息
     */
    public function getInfo(): array
    {
        return [
            'name' => $this->name,
            'display_name' => '示例插件',
            'version' => $this->version,
            'description' => $this->description,
            'author' => $this->author,
            'permissions' => $this->permissions,
            'dependencies' => $this->dependencies,
            'requirements' => [
                'php' => '>=8.1',
                'laravel' => '>=10.0',
            ],
        ];
    }

    /**
     * 插件启用时的处理
     */
    public function enable(): void
    {
        // 这里可以执行一些初始化逻辑
        // 比如创建数据库表、设置默认配置等

        \Log::info('ExamplePlugin: Plugin enabled');
    }

    /**
     * 插件禁用时的处理
     */
    public function disable(): void
    {
        // 这里可以执行一些清理逻辑
        // 比如删除临时文件、清理缓存等

        \Log::info('ExamplePlugin: Plugin disabled');
    }

    /**
     * 插件安装
     */
    public function install(): bool
    {
        try {
            // 执行安装逻辑
            \Log::info('ExamplePlugin: Plugin installed');
            return true;
        } catch (\Exception $e) {
            \Log::error('ExamplePlugin: Installation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 插件卸载
     */
    public function uninstall(): bool
    {
        try {
            // 执行卸载逻辑
            \Log::info('ExamplePlugin: Plugin uninstalled');
            return true;
        } catch (\Exception $e) {
            \Log::error('ExamplePlugin: Uninstallation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 插件注册后的回调
     */
    public function onRegistered(): void
    {
        \Log::info('ExamplePlugin: Plugin registered successfully');
    }
}
