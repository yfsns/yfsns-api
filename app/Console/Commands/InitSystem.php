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

use Exception;
use Illuminate\Console\Command;

class InitSystem extends Command
{
    /**
     * 命令名称.
     *
     * @var string
     */
    protected $signature = 'system:init {--force : 强制重新初始化}';

    /**
     * 命令描述.
     *
     * @var string
     */
    protected $description = '系统初始化（数据库迁移、创建默认用户角色和管理员账号）';

    /**
     * 执行命令.
     */
    public function handle()
    {
        if ($this->option('force')) {
            if (! $this->confirm('强制初始化将重置所有系统数据，是否继续？')) {
                return;
            }
        }

        $this->info('开始系统初始化...');

        try {
            // 步骤1：执行数据库迁移
            $this->info('1. 执行数据库迁移...');
            $this->call('migrate:fresh', [
                '--force' => $this->option('force'),
            ]);

            // 步骤2：初始化用户角色
            $this->info('2. 初始化系统用户角色...');
            $this->call('system:init-user-groups');

            // 步骤3：创建管理员账号
            $this->info('3. 创建管理员账号...');

            // 询问管理员账号信息
            $username = $this->ask('请输入管理员用户名', 'admin');
            $email = $this->ask('请输入管理员邮箱', 'admin@example.com');
            $password = $this->secret('请输入管理员密码（留空将自动生成）');

            $this->call('admin:create', [
                '--username' => $username,
                '--email' => $email,
                '--password' => $password,
            ]);

            $this->info('系统初始化完成！');

            // 显示提示信息
            $this->info('');
            $this->info('系统已经完成初始化，包括：');
            $this->info('1. 执行了数据库迁移');
            $this->info('2. 创建了默认用户角色（管理员、访客、注册用户、高级用户、VIP）');
            $this->info('3. 创建了管理员账号');
            $this->info('');
            $this->info('现在您可以：');
            $this->info('1. 使用管理员账号登录系统');
            $this->info('2. 开始创建其他用户');
            $this->info('3. 配置系统其他功能');
        } catch (Exception $e) {
            $this->error('系统初始化失败：' . $e->getMessage());
            $this->error('请检查错误信息并重试');

            return 1;
        }
    }
}
