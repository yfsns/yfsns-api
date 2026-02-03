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

namespace Plugins\AliyunSmsPlugin;

use App\Modules\PluginSystem\BasePlugin;
use Plugins\AliyunSmsPlugin\Services\AliyunSmsService;

/**
 * 阿里云短信插件
 */
class Plugin extends BasePlugin
{
    /**
     * 初始化插件
     */
    protected function initialize(): void
    {
        $this->name = 'AliyunSmsPlugin';
        $this->version = '1.0.0';
        $this->description = '阿里云短信服务插件，提供完整的阿里云短信功能';
        $this->author = 'YFSNS Team';
        $this->requirements = [
            'php' => '>=8.1.0',
            'laravel' => '>=10.0.0',
        ];
    }

    /**
     * 插件启用时的处理
     */
    protected function onEnable(): void
    {
        parent::onEnable();

        // 注册阿里云短信服务
        $this->registerAliyunSmsService();

        // 执行数据库迁移
        $this->runMigrations();

        // 加载API路由
        $this->loadRoutes();

        \Log::info('AliyunSmsPlugin enabled successfully');
    }

    /**
     * 注册阿里云短信服务
     */
    protected function registerAliyunSmsService(): void
    {
        app()->singleton(AliyunSmsService::class, function ($app) {
            return new AliyunSmsService();
        });

        // 替换内置的AliyunChannel
        app()->bind(\App\Modules\Sms\Contracts\SmsChannelInterface::class, function ($app) {
            // 这里可以根据配置动态选择使用插件还是内置实现
            return $app->make(AliyunSmsService::class);
        });
    }

    /**
     * 加载路由
     */
    protected function loadRoutes(): void
    {
        $routeFile = __DIR__ . '/Routes/api.php';

        if (file_exists($routeFile)) {
            \Route::middleware('api')
                ->prefix('api/plugins/aliyun-sms')
                ->group($routeFile);
        }
    }

    /**
     * 执行数据库迁移
     */
    protected function runMigrations(): void
    {
        $migrationPath = __DIR__ . '/Database/Migrations';

        if (is_dir($migrationPath)) {
            // 阿里云短信插件通常不需要额外的数据库表
            // 如果需要，可以在这里执行迁移
        }
    }

    /**
     * 获取插件配置定义
     */
    public function getConfigSchema(): array
    {
        return [
            'fields' => [
                'ALIYUN_SMS_ACCESS_KEY_ID' => [
                    'type' => 'password',
                    'label' => 'AccessKey ID',
                    'description' => '阿里云AccessKey ID',
                    'placeholder' => '请输入AccessKey ID',
                    'required' => true,
                    'validation' => 'required|string|min:10|max:64',
                    'group' => 'credentials',
                    'order' => 1
                ],
                'ALIYUN_SMS_ACCESS_KEY_SECRET' => [
                    'type' => 'password',
                    'label' => 'AccessKey Secret',
                    'description' => '阿里云AccessKey Secret',
                    'placeholder' => '请输入AccessKey Secret',
                    'required' => true,
                    'validation' => 'required|string|min:10|max:64',
                    'group' => 'credentials',
                    'order' => 2
                ],
                'ALIYUN_SMS_SIGN_NAME' => [
                    'type' => 'text',
                    'label' => '短信签名',
                    'description' => '短信签名名称',
                    'placeholder' => '请输入短信签名',
                    'required' => true,
                    'validation' => 'required|string|max:100',
                    'group' => 'sms',
                    'order' => 1
                ],
                'ALIYUN_SMS_REGION_ID' => [
                    'type' => 'select',
                    'label' => '地域节点',
                    'description' => '阿里云地域节点',
                    'options' => [
                        ['value' => 'cn-hangzhou', 'label' => '华东1 (杭州)'],
                        ['value' => 'cn-shanghai', 'label' => '华东2 (上海)'],
                        ['value' => 'cn-beijing', 'label' => '华北2 (北京)'],
                        ['value' => 'cn-shenzhen', 'label' => '华南1 (深圳)']
                    ],
                    'default' => 'cn-hangzhou',
                    'group' => 'sms',
                    'order' => 2
                ],
                'ALIYUN_SMS_ENABLED' => [
                    'type' => 'checkbox',
                    'label' => '启用阿里云短信',
                    'description' => '是否启用阿里云短信服务',
                    'default' => true,
                    'group' => 'general',
                    'order' => 1
                ]
            ],
            'groups' => [
                'general' => [
                    'label' => '通用设置',
                    'description' => '插件通用配置',
                    'icon' => 'settings',
                    'order' => 1
                ],
                'credentials' => [
                    'label' => 'API凭据',
                    'description' => '阿里云API访问凭据',
                    'icon' => 'key',
                    'order' => 2
                ],
                'sms' => [
                    'label' => '短信配置',
                    'description' => '短信服务相关配置',
                    'icon' => 'message',
                    'order' => 3
                ]
            ],
            'values' => [
                'ALIYUN_SMS_ACCESS_KEY_ID' => '',
                'ALIYUN_SMS_ACCESS_KEY_SECRET' => '',
                'ALIYUN_SMS_SIGN_NAME' => '',
                'ALIYUN_SMS_REGION_ID' => 'cn-hangzhou',
                'ALIYUN_SMS_ENABLED' => true
            ]
        ];
    }
}
