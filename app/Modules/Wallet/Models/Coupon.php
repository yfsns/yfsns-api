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
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 优惠券模型.
 *
 * 主要功能：
 * 1. 管理优惠券的基本信息
 * 2. 提供优惠券状态检查
 * 3. 计算优惠金额
 */
class Coupon extends Model
{
    /**
     * 可批量赋值的属性.
     */
    protected $fillable = [
        'template_id',  // 模板ID
        'user_id',      // 用户ID
        'name',         // 优惠券名称
        'type',         // 优惠券类型：fixed(固定金额)、discount(折扣)、free_shipping(免运费)
        'value',        // 优惠值：固定金额或折扣比例
        'min_amount',   // 最低使用金额
        'max_discount', // 最大优惠金额（折扣券使用）
        'start_time',   // 开始时间
        'end_time',     // 结束时间
        'status',       // 状态：active(激活)、inactive(未激活)、used(已使用)、expired(已过期)
        'used_time',    // 使用时间
        'order_id',      // 使用的订单ID
    ];

    /**
     * 属性类型转换.
     */
    protected $casts = [
        'template_id' => 'integer',
        'user_id' => 'integer',
        'value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'used_time' => 'datetime',
        'order_id' => 'integer',
    ];

    /**
     * 关联用户.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class);
    }

    /**
     * 关联模板
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CouponTemplate::class);
    }

    /**
     * 关联用户优惠券（兼容旧版本）.
     */
    public function userCoupons(): HasMany
    {
        return $this->hasMany(UserCoupon::class);
    }

    /**
     * 检查优惠券是否可用.
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && now()->between($this->start_time, $this->end_time);
    }

    /**
     * 计算优惠金额.
     *
     * @param float $amount 订单金额
     *
     * @return float 优惠金额
     */
    public function calculateDiscount($amount): float
    {
        if (! $this->isActive() || $amount < $this->min_amount) {
            return 0;
        }

        return $this->type === 'fixed'
            ? $this->value
            : $amount * ($this->value / 100);
    }
}
