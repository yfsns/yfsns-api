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
use Illuminate\Database\Eloquent\Relations\HasMany;

use function in_array;

/**
 * 优惠券模板模型.
 *
 * 主要功能：
 * 1. 定义优惠券的生成规则
 * 2. 支持批量生成优惠券
 * 3. 灵活的发放策略
 */
class CouponTemplate extends Model
{
    /**
     * 可批量赋值的属性.
     */
    protected $fillable = [
        'name',              // 模板名称
        'code',              // 模板代码（唯一标识）
        'description',        // 模板描述
        'type',              // 优惠券类型：fixed(固定金额)、discount(折扣)、free_shipping(免运费)
        'value',             // 优惠值
        'min_amount',        // 最低使用金额
        'max_discount',      // 最大优惠金额（折扣券使用）
        'quantity',          // 生成数量
        'issued_count',      // 已发放数量
        'used_count',        // 已使用数量
        'valid_days',        // 有效期天数
        'start_time',        // 开始时间
        'end_time',          // 结束时间
        'user_limit',        // 每个用户限制领取数量
        'category_ids',      // 适用分类ID（JSON格式）
        'exclude_category_ids', // 排除分类ID（JSON格式）
        'user_levels',       // 适用用户等级（JSON格式）
        'status',            // 状态：active(激活)、inactive(未激活)
        'auto_issue',        // 是否自动发放
        'issue_conditions',   // 发放条件（JSON格式）
    ];

    /**
     * 属性类型转换.
     */
    protected $casts = [
        'value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'quantity' => 'integer',
        'issued_count' => 'integer',
        'used_count' => 'integer',
        'valid_days' => 'integer',
        'user_limit' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'category_ids' => 'json',
        'exclude_category_ids' => 'json',
        'user_levels' => 'json',
        'auto_issue' => 'boolean',
        'issue_conditions' => 'json',
    ];

    /**
     * 关联优惠券.
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'template_id');
    }

    /**
     * 检查模板是否可用.
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && now()->between($this->start_time, $this->end_time)
            && $this->issued_count < $this->quantity;
    }

    /**
     * 检查用户是否可以领取.
     */
    public function canUserReceive(int $userId): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        // 检查用户等级限制
        if ($this->user_levels) {
            $user = \App\Modules\User\Models\User::find($userId);
            if (! $user || ! in_array($user->level ?? 0, $this->user_levels)) {
                return false;
            }
        }

        // 检查用户领取数量限制
        if ($this->user_limit > 0) {
            $userReceivedCount = $this->coupons()
                ->where('user_id', $userId)
                ->count();
            if ($userReceivedCount >= $this->user_limit) {
                return false;
            }
        }

        return true;
    }

    /**
     * 生成优惠券.
     */
    public function generateCoupon(int $userId): ?Coupon
    {
        if (! $this->canUserReceive($userId)) {
            return null;
        }

        $validUntil = $this->valid_days > 0
            ? now()->addDays($this->valid_days)
            : $this->end_time;

        $coupon = Coupon::create([
            'template_id' => $this->id,
            'user_id' => $userId,
            'name' => $this->name,
            'type' => $this->type,
            'value' => $this->value,
            'min_amount' => $this->min_amount,
            'max_discount' => $this->max_discount,
            'start_time' => $this->start_time,
            'end_time' => $validUntil,
            'status' => 'active',
        ]);

        if ($coupon) {
            $this->increment('issued_count');
        }

        return $coupon;
    }

    /**
     * 批量生成优惠券.
     */
    public function generateCoupons(array $userIds): int
    {
        $generatedCount = 0;

        foreach ($userIds as $userId) {
            if ($this->generateCoupon($userId)) {
                $generatedCount++;
            }
        }

        return $generatedCount;
    }

    /**
     * 计算优惠金额.
     */
    public function calculateDiscount(float $amount): float
    {
        if ($amount < $this->min_amount) {
            return 0;
        }

        $discount = 0;

        switch ($this->type) {
            case 'fixed':
                $discount = $this->value;

                break;

            case 'discount':
                $discount = $amount * ($this->value / 100);
                if ($this->max_discount > 0) {
                    $discount = min($discount, $this->max_discount);
                }

                break;

            case 'free_shipping':
                // 免运费逻辑，这里简化处理
                $discount = 0;

                break;
        }

        return min($discount, $amount);
    }

    /**
     * 获取剩余数量.
     */
    public function getRemainingQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->issued_count);
    }

    /**
     * 获取使用率.
     */
    public function getUsageRateAttribute(): float
    {
        if ($this->issued_count === 0) {
            return 0;
        }

        return round(($this->used_count / $this->issued_count) * 100, 2);
    }
}
