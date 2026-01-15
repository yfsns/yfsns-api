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

namespace App\Modules\System\Services;

use App\Modules\System\Models\Config;
use Illuminate\Support\Facades\Cache;

/**
 * 系统配置服务
 *
 * 专门管理系统级配置，不包含内容审核配置
 */
class ConfigService
{
    public const CACHE_PREFIX = 'system_config:';
    public const CACHE_MINUTES = 60;

    /**
     * 获取配置.
     */
    public function get(string $key, ?string $group = null, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . ($group ? $group . ':' : '') . $key;

        return Cache::remember($cacheKey, self::CACHE_MINUTES, function () use ($key, $group, $default) {
            $query = Config::where('key', $key);
            if ($group) {
                $query->where('group', $group);
            }
            $config = $query->first();
            if (!$config) {
                return $default;
            }

            return $this->parseValue($config->value, $config->type);
        });
    }

    /**
     * 设置配置.
     */
    public function set(string $key, $value, string $type = 'string', string $group = 'system', string $description = '', bool $isSystem = false)
    {
        $data = [
            'value' => $type === 'json' ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value,
            'type' => $type,
            'group' => $group,
            'description' => $description,
            'is_system' => $isSystem,
        ];
        $config = Config::updateOrCreate(['key' => $key], $data);
        $cacheKey = self::CACHE_PREFIX . ($group ? $group . ':' : '') . $key;
        Cache::forget($cacheKey);

        return $config;
    }

    /**
     * 清除缓存.
     */
    public function clearCache(?string $key = null, ?string $group = null): void
    {
        if ($key) {
            $cacheKey = self::CACHE_PREFIX . ($group ? $group . ':' : '') . $key;
            Cache::forget($cacheKey);
        } elseif ($group) {
            $cacheKey = self::CACHE_PREFIX . 'group:' . $group;
            Cache::forget($cacheKey);
        } else {
            // 清除所有系统配置缓存
            $configs = Config::all();
            foreach ($configs as $config) {
                $cacheKey = self::CACHE_PREFIX . ($config->group ? $config->group . ':' : '') . $config->key;
                Cache::forget($cacheKey);
                if ($config->group) {
                    Cache::forget(self::CACHE_PREFIX . 'group:' . $config->group);
                }
            }
        }
    }

    /**
     * 解析配置值
     */
    private function parseValue($value, string $type)
    {
        switch ($type) {
            case 'json':
                return json_decode($value, true);
            case 'boolean':
                return (bool) $value;
            case 'number':
                return is_numeric($value) ? (float) $value : $value;
            default:
                return $value;
        }
    }
}
