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

namespace App\Modules\Order\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use function in_array;

class Order extends Model
{
    /**
     * 订单状态常量.
     */
    public const STATUS_PENDING = 'pending';       // 待支付

    public const STATUS_PAID = 'paid';             // 已支付

    public const STATUS_CANCELLED = 'cancelled';   // 已取消

    public const STATUS_REFUNDING = 'refunding';   // 退款中

    public const STATUS_REFUNDED = 'refunded';     // 已退款

    public const STATUS_COMPLETED = 'completed';   // 已完成

    /**
     * 支付类型常量.
     */
    public const PAY_TYPE_BALANCE = 'balance';     // 余额支付

    public const PAY_TYPE_COIN = 'coin';           // 金币支付

    public const PAY_TYPE_ALIPAY = 'alipay';       // 支付宝

    public const PAY_TYPE_WECHAT = 'wechat';       // 微信支付

    /**
     * 支付状态常量.
     */
    public const PAYMENT_STATUS_UNPAID = 'unpaid';       // 未支付

    public const PAYMENT_STATUS_PAYING = 'paying';       // 支付中

    public const PAYMENT_STATUS_SUCCESS = 'success';     // 支付成功

    public const PAYMENT_STATUS_FAILED = 'failed';       // 支付失败

    protected $table = 'orders';

    protected $fillable = [
        'order_no',
        'user_id',
        'product_id',
        'quantity',
        'amount',
        'status',
        'pay_type',
        'payment_no',
        'paid_at',
        'subject',
        'remark',
        'payment_status',
        'payment_details',
        'payment_error_msg',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'quantity' => 'integer',
        'payment_details' => 'json',
    ];

    /**
     * 获取用户.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 判断订单是否可以取消.
     */
    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING]);
    }

    /**
     * 判断订单是否已支付.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * 判断订单是否可以退款.
     */
    public function canRefund(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_COMPLETED]);
    }

    /**
     * 获取订单状态文本.
     */
    public function getStatusTextAttribute(): string
    {
        $statusTexts = [
            self::STATUS_PENDING => '待支付',
            self::STATUS_PAID => '已支付',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_REFUNDING => '退款中',
            self::STATUS_REFUNDED => '已退款',
            self::STATUS_COMPLETED => '已完成',
        ];

        return $statusTexts[$this->status] ?? '未知';
    }

    /**
     * 获取支付状态文本.
     */
    public function getPaymentStatusTextAttribute(): string
    {
        $statusTexts = [
            self::PAYMENT_STATUS_UNPAID => '未支付',
            self::PAYMENT_STATUS_PAYING => '支付中',
            self::PAYMENT_STATUS_SUCCESS => '支付成功',
            self::PAYMENT_STATUS_FAILED => '支付失败',
        ];

        return $statusTexts[$this->payment_status] ?? '未知';
    }
}
