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

namespace App\Modules\Notification\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailConfig extends Model
{
    protected $table = 'email_servers';

    protected $fillable = [
        'driver',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'api_key',
        'domain',
        'secret',
        'region',
        'from_address',
        'from_name',
    ];

    /**
     * 默认属性值
     */
    protected $attributes = [
        'driver' => 'smtp',
    ];

    protected $casts = [
        'port' => 'integer',
    ];

    protected $hidden = [
        'password',
        'api_key',
        'secret',
    ];

    /**
     * 获取邮件配置（只有一条记录）.
     */
    public static function getConfig()
    {
        return static::first() ?: static::create([]);
    }

    /**
     * 获取配置数组（用于Laravel Mail配置）.
     */
    public function getConfigArray(): array
    {
        $config = [
            'driver' => $this->driver ?? 'smtp',
        ];

        // SMTP 配置
        if ($this->driver === 'smtp') {
            $config = array_merge($config, [
                'host' => $this->host,
                'port' => $this->port,
                'encryption' => $this->encryption ?? 'tls',
                'username' => $this->username,
                'password' => $this->password,
                'timeout' => $this->timeout,
            ]);
        }

        // API 驱动配置
        if (in_array($this->driver, ['mailgun', 'ses'])) {
            $config = array_merge($config, [
                'api_key' => $this->api_key,
                'domain' => $this->domain,
                'secret' => $this->secret,
                'region' => $this->region,
            ]);
        }

        // 发件人配置
        if ($this->from_address) {
            $config['from'] = [
                'address' => $this->from_address,
                'name' => $this->from_name,
            ];
        }

        return $config;
    }

    /**
     * 测试配置是否有效.
     */
    public function testConnection(): bool
    {
        // 这里可以添加实际的连接测试逻辑
        // 暂时返回true，实际项目中应该测试SMTP连接
        return true;
    }
}
