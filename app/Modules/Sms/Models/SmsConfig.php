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

namespace App\Modules\Sms\Models;

use App\Modules\System\Models\ServiceConfig;

/**
 * @deprecated 此模型已废弃，请使用 App\Modules\System\Models\ServiceConfig
 *
 * 短信配置模型 - 已迁移到统一的 ServiceConfig
 * 原表 sms_configs 已合并到 service_configs (group = 'sms')
 */
class SmsConfig extends ServiceConfig
{
    /**
     * 构造函数 - 自动设置分组为 sms
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->group = 'sms';
    }

    /**
     * 获取短信分组的配置
     */
    public function scopeSms($query)
    {
        return $query->where('group', 'sms');
    }

    /**
     * 获取当前启用的短信配置（同时只能有一个启用）.
     * @deprecated 使用 getDefaultByGroup('sms') 替代
     */
    public static function getEnabled()
    {
        return static::getDefaultByGroup('sms');
    }

    /**
     * 获取所有启用的短信配置列表.
     * @deprecated 使用 getAllEnabledByGroup('sms') 替代
     */
    public static function getAllEnabled()
    {
        return static::getAllEnabledByGroup('sms');
    }

    /**
     * 获取指定驱动的配置.
     */
    public static function getByDriver(string $driver)
    {
        return static::getByGroupAndDriver('sms', $driver);
    }
}
