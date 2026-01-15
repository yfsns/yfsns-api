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

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 交易记录模型
 *
 * 用于记录用户的资金和积分变动，支持多种交易类型和元数据存储
 */
class Transaction extends Model
{
    /**
     * 指定表名.
     */
    protected $table = 'wallet_balance_transactions';

    /**
     * 可批量赋值的属性.
     */
    protected $fillable = [
        'user_id',      // 用户ID
        'type',         // 交易类型：recharge(充值)、consume(消费)、points_earn(获取积分)、points_use(使用积分)
        'amount',       // 交易金额
        'points',       // 积分变动
        'description',  // 交易描述
        'metadata',      // 交易元数据
    ];

    /**
     * 属性类型转换.
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'points' => 'integer',
        'metadata' => 'json',
    ];

    /**
     * 关联用户模型.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 创建充值记录.
     *
     * @param int    $userId      用户ID
     * @param float  $amount      充值金额
     * @param string $description 描述
     */
    public static function createRecharge($userId, $amount, $description = '充值'): self
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'recharge',
            'amount' => $amount,
            'description' => $description,
        ]);
    }

    /**
     * 创建消费记录.
     *
     * @param int    $userId      用户ID
     * @param float  $amount      消费金额
     * @param string $description 描述
     */
    public static function createConsume($userId, $amount, $description = '消费'): self
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'consume',
            'amount' => -$amount,
            'description' => $description,
        ]);
    }

    /**
     * 创建积分获取记录.
     *
     * @param int    $userId      用户ID
     * @param int    $points      获取的积分
     * @param string $description 描述
     */
    public static function createPointsEarn($userId, $points, $description = '获取积分'): self
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'points_earn',
            'points' => $points,
            'description' => $description,
        ]);
    }

    /**
     * 创建积分使用记录.
     *
     * @param int    $userId      用户ID
     * @param int    $points      使用的积分
     * @param string $description 描述
     */
    public static function createPointsUse($userId, $points, $description = '使用积分'): self
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'points_use',
            'points' => -$points,
            'description' => $description,
        ]);
    }
}
