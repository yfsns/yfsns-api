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

namespace App\Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 订阅模型（支持多态订阅）.
 */
class Subscription extends Model
{
    // 订阅状态
    public const STATUS_ACTIVE = 'active';       // 有效

    public const STATUS_EXPIRED = 'expired';     // 已过期

    public const STATUS_CANCELLED = 'cancelled'; // 已取消

    protected $table = 'subscriptions';

    protected $fillable = [
        'user_id',
        'subscribable_type',
        'subscribable_id',
        'order_id',
        'price',
        'started_at',
        'expired_at',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'started_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    /**
     * 订阅用户.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class);
    }

    /**
     * 被订阅的对象（多态）.
     */
    public function subscribable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 关联订单.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Order\Models\Order::class);
    }

    /**
     * 检查订阅是否有效.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->expired_at
            && $this->expired_at->isFuture();
    }

    /**
     * 检查订阅是否已过期
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->expired_at && $this->expired_at->isPast());
    }
}
