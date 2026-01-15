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

namespace App\Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 钱包安全日志模型.
 *
 * 主要功能：
 * 1. 记录钱包安全相关事件
 * 2. 提供基本的安全审计功能
 */
class WalletSecurityLog extends Model
{
    /**
     * 可批量赋值的属性.
     */
    protected $fillable = [
        'user_id',        // 用户ID
        'action',         // 操作类型：freeze(冻结)、unfreeze(解冻)、limit_exceeded(超限)、password_failed(密码验证失败)
        'reason',         // 操作原因
        'ip_address',     // IP地址
        'user_agent',     // 用户代理
        'metadata',        // 额外信息（JSON格式）
    ];

    /**
     * 属性类型转换.
     */
    protected $casts = [
        'metadata' => 'json',
    ];

    /**
     * 关联用户.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class);
    }

    /**
     * 获取操作类型描述.
     */
    public function getActionDescriptionAttribute(): string
    {
        $descriptions = [
            'freeze' => '冻结钱包',
            'unfreeze' => '解冻钱包',
            'limit_exceeded' => '超限交易',
            'password_failed' => '密码验证失败',
            'suspicious' => '可疑交易',
        ];

        return $descriptions[$this->action] ?? $this->action;
    }

    /**
     * 记录安全事件.
     */
    public static function logSecurityEvent(
        int $userId,
        string $action,
        string $reason,
        array $metadata = []
    ): void {
        self::create([
            'user_id' => $userId,
            'action' => $action,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
