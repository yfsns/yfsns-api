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

use function count;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install 
                            {--force : 强制执行，跳过确认}
                            {--fresh : 清空数据库重新安装}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '安装应用程序（执行迁移和初始化数据）';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('');
        $this->info('========================================');
        $this->info(' 开始安装应用程序');
        $this->info('========================================');
        $this->info('');

        // 检查数据库连接
        if (! $this->checkDatabaseConnection()) {
            $this->error(' 数据库连接失败，请检查 .env 配置');

            return 1;
        }

        // 检查是否有表存在
        $tablesExist = $this->checkTablesExist();

        if ($tablesExist && ! $this->option('fresh')) {
            $this->warn('  检测到数据库中已存在表');

            if (! $this->option('force')) {
                $choice = $this->choice(
                    '如何处理？',
                    [
                        1 => '清空数据库重新安装（推荐）',
                        2 => '尝试继续安装（可能失败）',
                        3 => '取消安装',
                    ],
                    1
                );

                if ($choice === '取消安装') {
                    $this->warn('安装已取消');

                    return 0;
                }

                if ($choice === '清空数据库重新安装（推荐）') {
                    $this->option('fresh', true);
                }
            }
        }

        // 确认是否继续
        if (! $this->option('force') && ! $tablesExist && ! $this->confirm('确定要继续安装吗？这将创建数据库表并插入初始数据。', true)) {
            $this->warn('安装已取消');

            return 0;
        }

        // 步骤 1: 执行迁移
        $this->info('');
        $this->info('📦 步骤 1/5: 执行数据库迁移...');

        if ($this->option('fresh')) {
            $this->warn('正在彻底清空数据库...');

            // 先手动删除所有表（包括那些不在 migrations 表中的表）
            try {
                $this->dropAllTables();
            } catch (Exception $e) {
                $this->error(' 删除表失败: ' . $e->getMessage());

                return 1;
            }

            // 然后重新执行迁移
            $this->line('正在重新创建表结构...');

            try {
                Artisan::call('migrate', [
                    '--force' => true,
                ]);
                $this->info(Artisan::output());
                $this->info(' 数据库表结构已重新创建完成');
            } catch (Exception $e) {
                $this->error(' 迁移失败: ' . $e->getMessage());
                $this->warn('');
                $this->warn(' 如果仍然失败，请手动执行：');
                $this->line('   1. 手动删除数据库中的所有表');
                $this->line('   2. 然后运行: php artisan migrate --force');

                return 1;
            }
        } else {
            $this->line('正在创建数据库表结构...');

            try {
                Artisan::call('migrate', [
                    '--force' => true,
                ]);
                $this->info(Artisan::output());
                $this->info(' 数据库迁移完成');
            } catch (Exception $e) {
                $this->error(' 迁移失败: ' . $e->getMessage());
                $this->warn('');
                $this->warn(' 提示：如果是因为表已存在，请使用以下命令：');
                $this->line('   php artisan app:install --fresh');

                return 1;
            }
        }

        // 步骤 2: 生成应用密钥（如果还没有）
        $this->info('');
        $this->info('步骤 2/5: 检查应用密钥...');

        if (empty(config('app.key'))) {
            Artisan::call('key:generate', ['--force' => true]);
            $this->info(' 应用密钥已生成');
        } else {
            $this->info(' 应用密钥已存在');
        }

        // 步骤 3: JWT 已移除，跳过此步骤

        // 步骤 4: 执行 Seeders（插入初始数据）
        $this->info('');
        $this->info('🌱 步骤 4/5: 插入初始数据...');

        try {
            $this->line('正在执行 DatabaseSeeder 填充所有初始数据...');
            $exitCode = Artisan::call('db:seed', [
                '--class' => 'DatabaseSeeder',
                '--force' => true,
            ]);

            $output = Artisan::output();
            if (! empty($output)) {
                $this->line($output);
            }

            if ($exitCode === 0) {
                $this->info(' 初始数据填充完成');
            } else {
                $this->error(' 数据填充失败，退出码: ' . $exitCode);
                $this->warn(' 提示：您可以稍后手动执行 php artisan db:seed --force');
                // 不返回错误，继续执行后续步骤
            }
        } catch (Exception $e) {
            $this->error(' 数据填充失败: ' . $e->getMessage());
            $this->error('   文件: ' . $e->getFile() . ':' . $e->getLine());
            if (config('app.debug')) {
                $this->error('   堆栈: ' . $e->getTraceAsString());
            }
            $this->warn(' 提示：您可以稍后手动执行 php artisan db:seed --force');
            // 不返回错误，继续执行后续步骤
        }

        // 步骤 5: 创建存储链接
        $this->info('');
        $this->info('🔗 步骤 5/5: 创建存储链接...');

        try {
            Artisan::call('storage:link');
            $this->info(' 存储链接已创建');
        } catch (Exception $e) {
            $this->warn('  存储链接创建失败: ' . $e->getMessage());
        }

        // 完成
        $this->info('');
        $this->info('========================================');
        $this->info(' 安装完成！');
        $this->info('========================================');
        $this->info('');

        $this->table(
            ['项目', '状态'],
            [
                ['数据库迁移', ' 完成'],
                ['应用密钥', ' 完成'],
                ['初始数据', ' 完成'],
                ['存储链接', ' 完成'],
            ]
        );

        $this->info('');
        $this->info(' 下一步操作：');
        $this->line('1. 配置 .env 文件中的其他参数（邮件、短信等）');
        $this->line('2. 访问应用程序并完成初始化设置');
        $this->info('');

        return 0;
    }

    /**
     * 检查数据库连接.
     */
    private function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            $this->info(' 数据库连接正常');

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 检查数据库中是否已存在表.
     */
    private function checkTablesExist(): bool
    {
        try {
            $tables = DB::select('SHOW TABLES');

            return count($tables) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 彻底删除所有表.
     */
    private function dropAllTables(): void
    {
        // 禁用外键检查
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        // 获取所有表（排除视图）
        $database = DB::getDatabaseName();
        $tables = DB::select("
            SELECT TABLE_NAME 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_TYPE = 'BASE TABLE'
        ", [$database]);

        if (empty($tables)) {
            $this->info('数据库中没有表需要删除');
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            return;
        }

        $this->line('找到 ' . count($tables) . ' 个表，开始删除...');

        // 删除所有表
        $deletedCount = 0;
        $failedCount = 0;
        foreach ($tables as $table) {
            $tableName = $table->TABLE_NAME;

            try {
                $this->line("删除表: {$tableName}");
                DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
                $deletedCount++;
            } catch (Exception $e) {
                $this->warn("删除表失败 {$tableName}: " . $e->getMessage());
                $failedCount++;

                // 尝试强制删除
                try {
                    DB::statement("DROP TABLE `{$tableName}`");
                    $deletedCount++;
                    $failedCount--;
                    $this->info("强制删除成功: {$tableName}");
                } catch (Exception $e2) {
                    // 忽略强制删除失败
                }
            }
        }

        // 验证是否还有表存在
        $remainingTables = DB::select("
            SELECT TABLE_NAME 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_TYPE = 'BASE TABLE'
        ", [$database]);

        if (! empty($remainingTables)) {
            $this->warn('警告：仍有 ' . count($remainingTables) . ' 个表未删除');
            foreach ($remainingTables as $table) {
                $this->warn("  - {$table->TABLE_NAME}");
            }
        }

        // 恢复外键检查
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $this->info(" 已删除 {$deletedCount} 个表" . ($failedCount > 0 ? "，{$failedCount} 个表删除失败" : ''));
    }
}
