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

namespace App\Modules\SensitiveWord\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SensitiveWordServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册模块配置
        $this->registerModuleConfig();
    }

    /**
     * 引导服务
     */
    public function boot(): void
    {
        // 加载后台路由 - 只添加基础 api 中间件，认证中间件在路由文件中定义
        Route::prefix('api/admin')
            ->middleware(['api'])
            ->group(function (): void {
                $this->loadRoutesFrom(base_path('app/Modules/SensitiveWord/Routes/admin.php'));
            });

        // 加载迁移
        $this->loadMigrationsFrom(base_path('app/Modules/SensitiveWord/Database/Migrations'));
    }

    /**
     * 注册模块配置
     */
    protected function registerModuleConfig(): void
    {
        $configPath = base_path('app/Modules/SensitiveWord/Config');

        if (is_dir($configPath)) {
            $files = glob($configPath . '/*.php');

            foreach ($files as $file) {
                $configName = basename($file, '.php');
                $configData = require $file;

                // 合并到全局配置
                config(['sensitive_word.' . $configName => $configData]);

                // 同时设置简短的别名
                if ($configName === 'sensitive_word') {
                    config(['sensitive_word' => $configData]);
                }
            }
        }
    }
}
