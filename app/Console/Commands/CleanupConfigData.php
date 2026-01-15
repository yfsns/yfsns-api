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

namespace App\Console\Commands;

use App\Modules\System\Models\Config;

use function count;

use Illuminate\Console\Command;

use function in_array;
use function strlen;

class CleanupConfigData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:cleanup 
                            {--group= : 指定要清理的分组}
                            {--dry-run : 预览模式，不实际删除}
                            {--force : 强制清理，跳过确认}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清理配置表中的冗余数据';

    /**
     * 需要保留的核心配置项.
     */
    private array $coreConfigs = [
        'registration' => [
            // 注册方式配置
            'enabled',
            'methods',
            'require_verification',
            'email_verification',
            'sms_verification',
            'username_required',
            'email_required',
            'phone_required',
            'auto_approve',
            'welcome_message',
        ],
        'login' => [
            // 登录方式配置
            'methods',
            'max_attempts',
            'lockout_duration',
            'remember_me',
            'auto_logout',
            'session_timeout',
            'concurrent_sessions',
            'inactivity_timeout',
            'ip_restriction',
            'geolocation_check',
        ],
        'password' => [
            // 密码安全配置
            'min_length',
            'require_special',
            'require_number',
            'require_uppercase',
            'require_lowercase',
            'expire_days',
            'history_count',
            'prevent_common',
            'strength_check',
            'reset_methods',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $group = $this->option('group');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->info(' 预览模式 - 不会实际删除数据');
        }

        if ($group) {
            $this->cleanupGroup($group, $dryRun, $force);
        } else {
            $this->cleanupAllGroups($dryRun, $force);
        }

        return 0;
    }

    /**
     * 显示帮助信息.
     */
    public function getHelp(): string
    {
        return <<<'HELP'
用法示例:

  # 预览所有分组的清理情况（不实际删除）
  php artisan config:cleanup --dry-run

  # 清理指定分组
  php artisan config:cleanup --group=auth

  # 强制清理指定分组（跳过确认）
  php artisan config:cleanup --group=auth --force

  # 清理所有分组（跳过确认）
  php artisan config:cleanup --force

  # 预览指定分组的清理情况
  php artisan config:cleanup --group=auth --dry-run

注意事项:
  - 使用 --dry-run 选项可以预览将要删除的数据，不会实际删除
  - 使用 --force 选项可以跳过确认步骤
  - 建议先使用 --dry-run 预览，确认无误后再执行实际清理
  - 清理操作不可逆，请谨慎操作

HELP;
    }

    /**
     * 清理指定分组.
     */
    private function cleanupGroup(string $group, bool $dryRun, bool $force): void
    {
        if (! isset($this->coreConfigs[$group])) {
            $this->error(" 未知的分组: {$group}");
            $this->info('支持的分组: ' . implode(', ', array_keys($this->coreConfigs)));

            return;
        }

        $this->info("🧹 开始清理 {$group} 分组...");

        // 获取当前分组的所有配置
        $currentConfigs = Config::where('group', $group)->get();
        $coreKeys = $this->coreConfigs[$group];

        // 找出需要删除的配置项
        $toDelete = $currentConfigs->filter(function ($config) use ($coreKeys) {
            return ! in_array($config->key, $coreKeys);
        });

        if ($toDelete->isEmpty()) {
            $this->info(" {$group} 分组没有冗余数据需要清理");

            return;
        }

        $this->info(" {$group} 分组统计:");
        $this->info('  总配置项: ' . $currentConfigs->count());
        $this->info('  核心配置: ' . count($coreKeys));
        $this->info('  冗余配置: ' . $toDelete->count());

        // 显示将要删除的配置项
        $this->newLine();
        $this->info('  将要删除的配置项:');
        $toDelete->each(function ($config): void {
            $this->line("  - {$config->key} ({$config->type}) - {$config->description}");
        });

        if (! $dryRun) {
            if (! $force) {
                if (! $this->confirm("确认删除 {$group} 分组的 {$toDelete->count()} 个冗余配置项吗？")) {
                    $this->info(' 操作已取消');

                    return;
                }
            }

            // 执行删除
            $deletedCount = $toDelete->count();
            $toDelete->each(function ($config): void {
                $config->delete();
            });

            $this->info(" 成功删除 {$deletedCount} 个冗余配置项");
        }
    }

    /**
     * 清理所有分组.
     */
    private function cleanupAllGroups(bool $dryRun, bool $force): void
    {
        $this->info('🧹 开始清理所有分组的冗余数据...');

        $totalDeleted = 0;
        $groupStats = [];

        foreach (array_keys($this->coreConfigs) as $group) {
            $this->newLine();
            $this->line('=' . str_repeat('=', strlen($group) + 10) . '=');
            $this->info("处理分组: {$group}");
            $this->line('=' . str_repeat('=', strlen($group) + 10) . '=');

            // 获取当前分组的所有配置
            $currentConfigs = Config::where('group', $group)->get();
            $coreKeys = $this->coreConfigs[$group];

            // 找出需要删除的配置项
            $toDelete = $currentConfigs->filter(function ($config) use ($coreKeys) {
                return ! in_array($config->key, $coreKeys);
            });

            if ($toDelete->isEmpty()) {
                $this->info(" {$group} 分组没有冗余数据");
                $groupStats[$group] = [
                    'total' => $currentConfigs->count(),
                    'deleted' => 0,
                    'kept' => $currentConfigs->count(),
                ];

                continue;
            }

            $this->info(" {$group} 分组统计:");
            $this->info('  总配置项: ' . $currentConfigs->count());
            $this->info('  核心配置: ' . count($coreKeys));
            $this->info('  冗余配置: ' . $toDelete->count());

            if (! $dryRun) {
                if (! $force) {
                    if (! $this->confirm("确认删除 {$group} 分组的 {$toDelete->count()} 个冗余配置项吗？")) {
                        $this->info("  跳过 {$group} 分组");
                        $groupStats[$group] = [
                            'total' => $currentConfigs->count(),
                            'deleted' => 0,
                            'kept' => $currentConfigs->count(),
                        ];

                        continue;
                    }
                }

                // 执行删除
                $deletedCount = $toDelete->count();
                $toDelete->each(function ($config): void {
                    $config->delete();
                });

                $totalDeleted += $deletedCount;
                $this->info(" 成功删除 {$deletedCount} 个冗余配置项");

                $groupStats[$group] = [
                    'total' => $currentConfigs->count(),
                    'deleted' => $deletedCount,
                    'kept' => $currentConfigs->count() - $deletedCount,
                ];
            } else {
                $groupStats[$group] = [
                    'total' => $currentConfigs->count(),
                    'deleted' => $toDelete->count(),
                    'kept' => count($coreKeys),
                ];
            }
        }

        // 显示清理结果
        $this->newLine();
        $this->info('📋 清理结果汇总:');
        $this->table(
            ['分组', '总配置项', '删除项', '保留项'],
            collect($groupStats)->map(function ($stats, $group) {
                return [
                    $group,
                    $stats['total'],
                    $stats['deleted'],
                    $stats['kept'],
                ];
            })->toArray()
        );

        if (! $dryRun) {
            $this->info(" 清理完成！总共删除了 {$totalDeleted} 个冗余配置项");
        } else {
            $this->info(' 预览完成！预计可删除 ' . collect($groupStats)->sum('deleted') . ' 个冗余配置项');
        }
    }
}
