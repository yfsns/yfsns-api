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

namespace App\Modules\System\Database\Seeders;

use App\Modules\System\Models\Config;
use Illuminate\Database\Seeder;

/**
 * 系统配置数据填充
 */
class SystemConfigSeeder extends Seeder
{
    /**
     * 运行数据填充
     */
    public function run(): void
    {
        $configs = [
            // ========== 认证配置 ==========
            [
                'key' => 'registration_methods',
                'value' => 'username,email,sms',
                'type' => 'array',
                'group' => 'auth',
                'description' => '注册方式（多选：username,email,sms）',
                'is_system' => false,
            ],
            [
                'key' => 'login_methods',
                'value' => 'username,email,sms',
                'type' => 'array',
                'group' => 'auth',
                'description' => '登录方式（多选：username,email,sms）',
                'is_system' => false,
            ],
            [
                'key' => 'password_strength',
                'value' => 'medium',
                'type' => 'select',
                'group' => 'auth',
                'description' => '密码强度要求（weak/medium/strong）',
                'is_system' => false,
            ],
            [
                'key' => 'login_attempts_limit',
                'value' => '5',
                'type' => 'integer',
                'group' => 'auth',
                'description' => '登录失败最大尝试次数',
                'is_system' => false,
            ],
            [
                'key' => 'login_lockout_duration',
                'value' => '900',
                'type' => 'integer',
                'group' => 'auth',
                'description' => '登录锁定持续时间（秒）',
                'is_system' => false,
            ],

            // ========== 通知配置 ==========
            [
                'key' => 'enable_login_notifications',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => '是否开启登录成功通知',
                'is_system' => false,
            ],
            [
                'key' => 'enable_like_notifications',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => '是否开启点赞通知',
                'is_system' => false,
            ],
            [
                'key' => 'enable_mention_notifications',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => '是否开启@提及通知',
                'is_system' => false,
            ],
            [
                'key' => 'enable_comment_notifications',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => '是否开启评论回复通知',
                'is_system' => false,
            ],

            // ========== 验证码配置 ==========
            [
                'key' => 'verification_code_length',
                'value' => '6',
                'type' => 'integer',
                'group' => 'verification',
                'description' => '验证码长度',
                'is_system' => false,
            ],
            [
                'key' => 'verification_code_ttl',
                'value' => '300',
                'type' => 'integer',
                'group' => 'verification',
                'description' => '验证码过期时间（秒）',
                'is_system' => false,
            ],
            [
                'key' => 'verification_max_attempts',
                'value' => '5',
                'type' => 'integer',
                'group' => 'verification',
                'description' => '验证码最大验证次数',
                'is_system' => false,
            ],
            [
                'key' => 'verification_send_limit',
                'value' => '60',
                'type' => 'integer',
                'group' => 'verification',
                'description' => '验证码发送频率限制（秒）',
                'is_system' => false,
            ],

            // ========== 内容配置 ==========
            [
                'key' => 'post_content_max_length',
                'value' => '10000',
                'type' => 'integer',
                'group' => 'content',
                'description' => '动态内容最大长度',
                'is_system' => false,
            ],
            [
                'key' => 'comment_content_max_length',
                'value' => '2000',
                'type' => 'integer',
                'group' => 'content',
                'description' => '评论内容最大长度',
                'is_system' => false,
            ],
            [
                'key' => 'nickname_max_length',
                'value' => '50',
                'type' => 'integer',
                'group' => 'content',
                'description' => '昵称最大长度',
                'is_system' => false,
            ],

            // ========== 文件上传配置 ==========
            [
                'key' => 'max_file_size',
                'value' => '10485760',
                'type' => 'integer',
                'group' => 'upload',
                'description' => '最大文件上传大小（字节，10MB）',
                'is_system' => false,
            ],
            [
                'key' => 'allowed_image_types',
                'value' => 'jpg,jpeg,png,gif,webp',
                'type' => 'string',
                'group' => 'upload',
                'description' => '允许的图片文件类型',
                'is_system' => false,
            ],
            [
                'key' => 'max_image_width',
                'value' => '4096',
                'type' => 'integer',
                'group' => 'upload',
                'description' => '图片最大宽度',
                'is_system' => false,
            ],
            [
                'key' => 'max_image_height',
                'value' => '4096',
                'type' => 'integer',
                'group' => 'upload',
                'description' => '图片最大高度',
                'is_system' => false,
            ],

            // ========== 缓存配置 ==========
            [
                'key' => 'cache_ttl_default',
                'value' => '3600',
                'type' => 'integer',
                'group' => 'cache',
                'description' => '默认缓存时间（秒）',
                'is_system' => false,
            ],
            [
                'key' => 'cache_ttl_long',
                'value' => '86400',
                'type' => 'integer',
                'group' => 'cache',
                'description' => '长时间缓存（秒，1天）',
                'is_system' => false,
            ],

            // ========== 系统配置 ==========
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'system',
                'description' => '系统维护模式',
                'is_system' => false,
            ],
            [
                'key' => 'debug_mode',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'system',
                'description' => '调试模式',
                'is_system' => false,
            ],
            [
                'key' => 'timezone',
                'value' => 'Asia/Shanghai',
                'type' => 'string',
                'group' => 'system',
                'description' => '系统时区',
                'is_system' => false,
            ],
        ];

        foreach ($configs as $config) {
            Config::updateOrCreate(
                [
                    'key' => $config['key'],
                    'group' => $config['group']
                ],
                $config
            );
        }

        $this->command->info('系统配置数据填充完成');
    }
}
