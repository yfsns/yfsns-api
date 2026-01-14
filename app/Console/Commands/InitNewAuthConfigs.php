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

use App\Modules\System\Services\ConfigService;
use Exception;
use Illuminate\Console\Command;

class InitNewAuthConfigs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:init-new 
                            {--force : 强制重新初始化}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '初始化新的三个核心分组配置项（每个分组一条JSON记录）';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');

        if ($force) {
            $this->info('强制重新初始化新配置...');
        } else {
            $this->info('初始化新的三个核心分组配置（每个分组一条JSON记录）...');
        }

        $configService = app(\App\Modules\System\Services\ConfigService::class);

        // 定义三个核心分组的配置（每个分组一条JSON记录）
        $configs = [
            'registration' => [
                'key' => 'registration_config',
                'value' => [
                    'methods' => ['username', 'email', 'sms'],
                ],
                'description' => '用户注册配置（JSON格式）',
            ],

            'login' => [
                'key' => 'login_config',
                'value' => [
                    'methods' => ['username', 'email', 'sms'],
                ],
                'description' => '用户登录配置（JSON格式）',
            ],

            'password' => [
                'key' => 'password_config',
                'value' => [
                    'min_length' => 6,
                    'strong_password' => false,
                ],
                'description' => '密码安全配置（JSON格式）',
            ],

            'storage' => [
                'key' => 'storage_config',
                'value' => [
                    'default_location' => 'local',
                    'max_file_size' => 100,
                    'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'],
                    'image_compression' => [
                        'enabled' => true,
                        'max_width' => 1920,
                        'max_height' => 1080,
                        'quality' => 85,
                    ],
                    'thumbnail' => [
                        'enabled' => true,
                        'width' => 300,
                        'height' => 300,
                    ],
                    'watermark' => [
                        'enabled' => false,
                        'text' => 'YFSNs',
                        'position' => 'bottom-right',
                    ],
                ],
                'description' => '文件存储配置（JSON格式）',
            ],
        ];

        try {
            $createdConfigs = 0;

            foreach ($configs as $group => $config) {
                $this->info(" 初始化 {$group} 分组...");

                // 检查是否已存在该分组的配置
                $existingCount = \App\Modules\System\Models\Config::where('group', $group)->count();

                if ($existingCount > 0 && ! $force) {
                    $this->warn("  {$group} 分组已存在 {$existingCount} 项配置，跳过");

                    continue;
                }

                if ($force && $existingCount > 0) {
                    // 删除现有配置
                    \App\Modules\System\Models\Config::where('group', $group)->delete();
                    $this->info("    已删除现有 {$group} 分组配置");
                }

                // 创建一条JSON配置记录
                $configService->set(
                    $config['key'],
                    $config['value'],
                    'json',
                    $group,
                    $config['description'],
                    false // 不是系统配置
                );

                $this->info("   {$group} 分组初始化完成，创建了 1 条JSON配置记录");
                $createdConfigs++;
            }

            $this->newLine();
            $this->info(' 所有分组初始化完成！');
            $this->info(" 总计创建了 {$createdConfigs} 条配置记录");

            // 显示配置摘要
            $this->newLine();
            $this->info('📋 配置摘要：');
            $this->table(
                ['分组', '记录数', '存储方式', '状态'],
                [
                    ['registration', 1, 'JSON', ' 已初始化'],
                    ['login', 1, 'JSON', ' 已初始化'],
                    ['password', 1, 'JSON', ' 已初始化'],
                ]
            );

            $this->newLine();
            $this->info(' 现在可以使用以下命令查看配置：');
            $this->line('  php artisan config:cleanup --dry-run  # 查看配置状态');
            $this->line('  php artisan tinker                     # 进入交互式环境查看数据');
        } catch (Exception $e) {
            $this->error('初始化失败：' . $e->getMessage());

            return 1;
        }

        return 0;
    }
}
