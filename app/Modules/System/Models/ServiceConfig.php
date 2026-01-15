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

namespace App\Modules\System\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceConfig extends Model
{
    /**
     * 表名.
     */
    protected $table = 'service_configs';

    /**
     * 可批量赋值的属性.
     */
    protected $fillable = [
        'group',
        'driver',
        'name',
        'config',
        'is_default',
        'status',
    ];

    /**
     * 应该被转换为原生类型的属性.
     */
    protected $casts = [
        'config' => 'array',
        'is_default' => 'boolean',
        'status' => 'boolean',
    ];

    /**
     * 获取指定分组和驱动的配置.
     */
    public static function getByGroupAndDriver(string $group, string $driver)
    {
        return static::where('group', $group)
            ->where('driver', $driver)
            ->where('status', true)
            ->first();
    }

    /**
     * 获取指定分组的默认配置.
     */
    public static function getDefaultByGroup(string $group)
    {
        return static::where('group', $group)
            ->where('is_default', true)
            ->where('status', true)
            ->first();
    }

    /**
     * 获取指定分组的所有启用配置.
     */
    public static function getAllEnabledByGroup(string $group)
    {
        return static::where('group', $group)
            ->where('status', true)
            ->get();
    }

    /**
     * 获取指定分组的所有配置（包括禁用）.
     */
    public static function getAllByGroup(string $group)
    {
        return static::where('group', $group)->get();
    }
}
