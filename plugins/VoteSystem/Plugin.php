<?php

/**
 * 投票系统插件
 *
 * 提供完整的投票功能，包括：
 * - 投票主题管理
 * - 投票选项管理
 * - 投票记录统计
 * - 投票权限控制
 * - 投票结果展示
 */

namespace Plugins\VoteSystem;

use App\Modules\PluginSystem\BasePlugin;
use App\Modules\PluginSystem\Contracts\PluginInterface;
use Illuminate\Support\Facades\Route;

class Plugin extends BasePlugin
{
    public function getInfo(): array
    {
        return [
            'name' => 'votesystem',
            'version' => '1.0.0',
            'description' => '完整的投票系统插件，支持多种投票类型和权限控制',
            'author' => 'YFSNS Team',
            'dependencies' => [],
            'permissions' => [
                'vote.create' => '创建投票',
                'vote.edit' => '编辑投票',
                'vote.delete' => '删除投票',
                'vote.view' => '查看投票',
                'vote.vote' => '参与投票',
                'vote.manage' => '管理投票',
            ],
        ];
    }

    /**
     * 初始化插件
     */
    protected function initialize(): void
    {
        $this->name = 'votesystem';
        $this->version = '1.0.0';
        $this->description = '完整的投票系统插件，支持多种投票类型和权限控制';
        $this->author = 'YFSNS Team';
        $this->dependencies = [];
        $this->enabled = false;
    }

    public function enable(): void
    {
        parent::enable();

        // 注册路由
        $this->registerRoutes();

        // 注册权限
        $this->registerPermissions();

        // 执行启用后的初始化
        $this->afterEnable();
    }

    public function disable(): void
    {
        // 清理权限
        $this->unregisterPermissions();

        parent::disable();
    }

    public function install(): bool
    {
        try {
            // 执行数据库迁移
            $this->runMigrations();

            // 执行数据填充
            $this->runSeeders();

            // 创建必要的目录
            $this->createDirectories();

            return true;
        } catch (\Exception $e) {
            \Log::error('VoteSystem plugin installation failed: ' . $e->getMessage());
            return false;
        }
    }

    public function uninstall(): bool
    {
        try {
            // 清理数据（可选，根据用户配置决定是否保留数据）
            if (config('plugins.vote_system.keep_data_on_uninstall', false)) {
                // 保留数据，只删除迁移记录
                $this->rollbackMigrations();
            } else {
                // 删除所有数据
                $this->dropTables();
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('VoteSystem plugin uninstallation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 注册路由
     */
    protected function registerRoutes($routes = null): void
    {
        // API路由
        Route::middleware(['api'])
            ->prefix('api/v1/plugins/vote-system')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
            });

        // 管理后台路由
        Route::middleware(['api', 'auth:api', 'admin'])
            ->prefix('api/admin/plugins/vote-system')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/routes/admin.php');
            });
    }

    /**
     * 注册权限
     */
    protected function registerPermissions(): void
    {
        // 这里可以注册插件的权限到系统权限表
        // 具体实现取决于你的权限系统
    }

    /**
     * 注销权限
     */
    protected function unregisterPermissions(): void
    {
        // 清理插件权限
    }

    /**
     * 启用后的初始化
     */
    protected function afterEnable(): void
    {
        // 可以在这里执行一些初始化逻辑
        // 如创建默认投票、设置默认配置等
    }

    /**
     * 执行数据库迁移
     */
    protected function runMigrations(): void
    {
        $migrationPath = __DIR__ . '/database/migrations';
        if (is_dir($migrationPath)) {
            // 这里可以调用Artisan命令执行迁移
            // 或者直接执行迁移文件
        }
    }

    /**
     * 回滚数据库迁移
     */
    protected function rollbackMigrations(): void
    {
        // 回滚迁移
    }

    /**
     * 删除数据表
     */
    protected function dropTables(): void
    {
        // 删除插件相关的数据表
    }

    /**
     * 执行数据填充
     */
    protected function runSeeders(): void
    {
        $seederPath = __DIR__ . '/database/seeders';
        if (is_dir($seederPath)) {
            // 执行种子文件
        }
    }

    /**
     * 创建必要的目录
     */
    protected function createDirectories(): void
    {
        $dirs = [
            storage_path('app/plugins/vote-system'),
            public_path('plugins/vote-system'),
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * 获取插件配置
     */
    protected function getConfig($key, $default = null)
    {
        $config = config('plugins.vote_system', []);

        if ($key === null) {
            return $config;
        }

        return data_get($config, $key, $default);
    }

    /**
     * 设置插件配置
     */
    protected function setConfig($key, $value): void
    {
        $config = config('plugins.vote_system', []);
        data_set($config, $key, $value);
        config(['plugins.vote_system' => $config]);

        // 保存到配置文件或数据库
        $this->saveConfig($config);
    }

    /**
     * 保存配置
     */
    protected function saveConfig(array $config): void
    {
        // 保存配置到文件或数据库
    }

    /**
     * 获取插件菜单
     */
    public function getMenuItems(): array
    {
        return [
            [
                'title' => '投票管理',
                'icon' => 'vote',
                'url' => '/admin/plugins/vote-system',
                'permission' => 'vote.manage',
                'children' => [
                    [
                        'title' => '投票列表',
                        'url' => '/admin/plugins/vote-system/votes',
                        'permission' => 'vote.view',
                    ],
                    [
                        'title' => '创建投票',
                        'url' => '/admin/plugins/vote-system/votes/create',
                        'permission' => 'vote.create',
                    ],
                    [
                        'title' => '投票统计',
                        'url' => '/admin/plugins/vote-system/stats',
                        'permission' => 'vote.view',
                    ],
                ],
            ],
        ];
    }

    /**
     * 获取插件导航栏菜单
     */
    public function getNavigationItems(): array
    {
        return [
            [
                'title' => '投票',
                'url' => '/votes',
                'icon' => 'vote',
                'permission' => 'vote.view',
            ],
        ];
    }

    /**
     * 插件钩子：用户注册后
     */
    public function onUserRegistered($user): void
    {
        // 用户注册后的处理逻辑
    }

    /**
     * 插件钩子：内容发布后
     */
    public function onContentPublished($content): void
    {
        // 内容发布后的处理逻辑
    }

    /**
     * 获取插件统计信息
     */
    public function getStats(): array
    {
        return [
            'total_votes' => 0, // 总投票数
            'active_votes' => 0, // 活跃投票数
            'total_participants' => 0, // 总参与人数
        ];
    }
}
