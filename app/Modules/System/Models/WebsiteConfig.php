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

class WebsiteConfig extends Model
{
    protected $fillable = [
        'site_name',
        'site_url',
        'site_description',
        'site_keywords',
        'site_title',
        'site_logo',
        'site_header_logo',
        'site_favicon',
        'site_status',
        'icp_number',
        'police_record',
        'statistics_code',
    ];

    protected $casts = [
        'site_status' => 'boolean',
    ];

    /**
     * 获取默认配置数据.
     */
    public static function getDefaultConfig(): array
    {
        return [
            'site_name' => '',
            'site_url' => '',
            'site_description' => '',
            'site_keywords' => '',
            'site_title' => '',
            'site_logo' => '',
            'site_header_logo' => '',
            'site_favicon' => '',
            'site_status' => true,
            'icp_number' => '',
            'police_record' => '',
            'statistics_code' => '',
        ];
    }

    /**
     * 过滤数据，只保留 fillable 字段.
     */
    public static function filterFillableData(array $data): array
    {
        return array_intersect_key($data, array_flip((new static())->getFillable()));
    }

    /**
     * 获取第一条配置记录.
     */
    public static function getFirstConfig()
    {
        return static::first();
    }

    /**
     * 获取或创建配置记录（只保留一条记录，id=1）.
     */
    public static function getOrCreateConfig()
    {
        $config = static::getFirstConfig();
        if (!$config) {
            $config = static::create(array_merge(['id' => 1], static::getDefaultConfig()));
        }

        if (!$config) {
            throw new \Exception('Failed to get or create website configuration');
        }

        return $config;
    }

    /**
     * 创建默认配置对象（用于缓存时返回，避免前端undefined错误）.
     */
    public static function createDefaultInstance(): static
    {
        $instance = new static();
        $instance->id = 1;
        foreach (static::getDefaultConfig() as $key => $value) {
            $instance->{$key} = $value;
        }
        return $instance;
    }
}
