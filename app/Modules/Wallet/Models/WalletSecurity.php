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
use Illuminate\Support\Facades\DB;

/**
 * 钱包安全设置模型.
 *
 * 主要功能：
 * 1. 管理用户钱包的基本安全策略
 * 2. 设置基本的交易限额
 * 3. 管理支付密码
 */
class WalletSecurity extends Model
{
    /**
     * 可批量赋值的属性.
     */
    protected $fillable = [
        'user_id',           // 用户ID
        'daily_limit',       // 每日交易限额
        'single_limit',      // 单笔交易限额
        'monthly_limit',     // 每月交易限额
        'password_enabled',  // 是否启用支付密码
        'payment_password',  // 支付密码（加密存储）
        'status',             // 状态：active(正常)、suspended(暂停)
    ];

    /**
     * 属性类型转换.
     */
    protected $casts = [
        'daily_limit' => 'decimal:2',
        'single_limit' => 'decimal:2',
        'monthly_limit' => 'decimal:2',
        'password_enabled' => 'boolean',
    ];

    /**
     * 关联用户.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class);
    }

    /**
     * 检查交易是否超过限额.
     */
    public function checkTransactionLimit(float $amount, string $period = 'single'): bool
    {
        switch ($period) {
            case 'single':
                return $this->single_limit == 0 || $amount <= $this->single_limit;
            case 'daily':
                return $this->daily_limit == 0 || $this->getDailyTotal() + $amount <= $this->daily_limit;
            case 'monthly':
                return $this->monthly_limit == 0 || $this->getMonthlyTotal() + $amount <= $this->monthly_limit;
            default:
                return true;
        }
    }

    /**
     * 获取当日交易总额.
     */
    public function getDailyTotal(): float
    {
        return Transaction::where('user_id', $this->user_id)
            ->where('type', 'consume')
            ->whereDate('created_at', today())
            ->sum(DB::raw('ABS(amount)'));
    }

    /**
     * 获取当月交易总额.
     */
    public function getMonthlyTotal(): float
    {
        return Transaction::where('user_id', $this->user_id)
            ->where('type', 'consume')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum(DB::raw('ABS(amount)'));
    }

    /**
     * 设置支付密码
     */
    public function setPaymentPassword(string $password): void
    {
        $this->update([
            'payment_password' => bcrypt($password),
            'password_enabled' => true,
        ]);
    }

    /**
     * 验证支付密码
     */
    public function verifyPaymentPassword(string $password): bool
    {
        if (! $this->password_enabled) {
            return true;
        }

        return password_verify($password, $this->payment_password);
    }

    /**
     * 检查钱包是否可用.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * 暂停钱包.
     */
    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    /**
     * 激活钱包.
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }
}
