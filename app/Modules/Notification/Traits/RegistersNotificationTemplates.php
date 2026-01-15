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

namespace App\Modules\Notification\Traits;

use App\Modules\Notification\Models\NotificationTemplate;

use function get_class;

trait RegistersNotificationTemplates
{
    /**
     * 注册通知模板
     *
     * @param array $templates 模板配置数组
     */
    protected function registerNotificationTemplates(array $templates): void
    {
        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['code' => $template['code']],
                $template
            );
        }
    }

    /**
     * 获取模块名称（用于日志）.
     */
    protected function getModuleName(): string
    {
        $class = get_class($this);
        $parts = explode('\\', $class);

        // 从 App\Modules\User\Providers\UserServiceProvider 提取 User
        if (isset($parts[2])) {
            return $parts[2];
        }

        return 'Unknown';
    }
}
