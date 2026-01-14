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
 * 音符币交易记录模型.
 *
 * 主要功能：
 * 1. 记录所有音符币变动
 * 2. 提供不同类型的交易记录创建方法
 * 3. 支持交易元数据存储
 */
class CoinTransaction extends Model
{
    /**
     * 可批量赋值的属性.
     */
    protected $fillable = [
        'user_id',      // 用户ID
        'target_user_id', // 目标用户ID（打赏时使用）
        'type',         // 交易类型：recharge(充值)、tip(打赏)、reward(奖励)、consume(消费)
        'coins',        // 音符币变动数量
        'rmb_amount',   // 对应的人民币金额
        'description',  // 交易描述
        'metadata',     // 交易元数据
        'status',        // 交易状态：pending(待处理)、completed(已完成)、failed(失败)
    ];

    /**
     * 属性类型转换.
     */
    protected $casts = [
        'coins' => 'integer',
        'rmb_amount' => 'decimal:2',
        'metadata' => 'json',
    ];

    /**
     * 关联用户模型.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class);
    }

    /**
     * 关联目标用户模型.
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class, 'target_user_id');
    }

    /**
     * 创建充值记录.
     *
     * @param int    $userId      用户ID
     * @param int    $coins       音符币数量
     * @param float  $rmbAmount   人民币金额
     * @param string $description 描述
     */
    public static function createRecharge(int $userId, int $coins, float $rmbAmount, string $description = '充值音符币'): self
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'recharge',
            'coins' => $coins,
            'rmb_amount' => $rmbAmount,
            'description' => $description,
            'status' => 'completed',
        ]);
    }

    /**
     * 创建打赏记录.
     *
     * @param int    $fromUserId  打赏用户ID
     * @param int    $toUserId    被打赏用户ID
     * @param int    $coins       音符币数量
     * @param string $description 描述
     * @param array  $metadata    元数据
     */
    public static function createTip(int $fromUserId, int $toUserId, int $coins, string $description = '打赏', array $metadata = []): self
    {
        return self::create([
            'user_id' => $fromUserId,
            'target_user_id' => $toUserId,
            'type' => 'tip',
            'coins' => -$coins, // 打赏者减少
            'rmb_amount' => $coins * 0.1,
            'description' => $description,
            'metadata' => $metadata,
            'status' => 'completed',
        ]);
    }

    /**
     * 创建被打赏记录.
     *
     * @param int    $fromUserId  打赏用户ID
     * @param int    $toUserId    被打赏用户ID
     * @param int    $coins       音符币数量
     * @param string $description 描述
     * @param array  $metadata    元数据
     */
    public static function createReceivedTip(int $fromUserId, int $toUserId, int $coins, string $description = '收到打赏', array $metadata = []): self
    {
        return self::create([
            'user_id' => $toUserId,
            'target_user_id' => $fromUserId,
            'type' => 'tip',
            'coins' => $coins, // 被打赏者增加
            'rmb_amount' => $coins * 0.1,
            'description' => $description,
            'metadata' => $metadata,
            'status' => 'completed',
        ]);
    }

    /**
     * 创建奖励记录.
     *
     * @param int    $userId      用户ID
     * @param int    $coins       音符币数量
     * @param string $description 描述
     * @param array  $metadata    元数据
     */
    public static function createReward(int $userId, int $coins, string $description = '获得奖励', array $metadata = []): self
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'reward',
            'coins' => $coins,
            'rmb_amount' => $coins * 0.1,
            'description' => $description,
            'metadata' => $metadata,
            'status' => 'completed',
        ]);
    }

    /**
     * 创建消费记录.
     *
     * @param int    $userId      用户ID
     * @param int    $coins       音符币数量
     * @param string $description 描述
     * @param array  $metadata    元数据
     */
    public static function createConsume(int $userId, int $coins, string $description = '消费音符币', array $metadata = []): self
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'consume',
            'coins' => -$coins,
            'rmb_amount' => $coins * 0.1,
            'description' => $description,
            'metadata' => $metadata,
            'status' => 'completed',
        ]);
    }

    /**
     * 获取交易类型描述.
     */
    public function getTypeDescriptionAttribute(): string
    {
        $descriptions = [
            'recharge' => '充值',
            'tip' => '打赏',
            'reward' => '奖励',
            'consume' => '消费',
        ];

        return $descriptions[$this->type] ?? $this->type;
    }

    /**
     * 获取音符币变动类型.
     */
    public function getCoinChangeTypeAttribute(): string
    {
        return $this->coins > 0 ? 'increase' : 'decrease';
    }

    /**
     * 获取音符币变动绝对值
     */
    public function getAbsoluteCoinsAttribute(): int
    {
        return abs($this->coins);
    }
}
