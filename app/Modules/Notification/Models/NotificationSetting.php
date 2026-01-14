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

use Illuminate\Database\Eloquent\Model;

use function in_array;

class NotificationSetting extends Model
{
    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'type',
        'channels',
        'preferences',
    ];

    protected $casts = [
        'channels' => 'array',
        'preferences' => 'array',
    ];

    /**
     * 获取通知对象
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * 检查通道是否启用.
     */
    public function isChannelEnabled(string $channel): bool
    {
        return in_array($channel, $this->channels);
    }

    /**
     * 获取通知偏好设置.
     *
     * @param null|mixed $default
     */
    public function getPreference(string $key, $default = null)
    {
        return $this->preferences[$key] ?? $default;
    }

    /**
     * 设置通知偏好.
     *
     * @return $this
     */
    public function setPreference(string $key, $value): self
    {
        $preferences = $this->preferences;
        $preferences[$key] = $value;
        $this->preferences = $preferences;

        return $this;
    }
}
