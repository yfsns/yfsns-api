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
 * 虚拟币模型（音符币）.
 *
 * 主要功能：
 * 1. 管理用户音符币余额
 * 2. 提供音符币的增减方法
 * 3. 使用乐观锁机制防止并发问题
 * 4. 支持打赏、充值等社交功能
 */
class VirtualCoin extends Model
{
    /**
     * 可批量赋值的属性.
     */
    protected $fillable = [
        'user_id',    // 用户ID
        'coins',      // 音符币余额
        'version',     // 版本号，用于乐观锁
    ];

    /**
     * 属性类型转换.
     */
    protected $casts = [
        'coins' => 'integer',     // 音符币为整数
        'version' => 'integer',    // 版本号为整数
    ];

    /**
     * 关联用户模型.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class);
    }

    /**
     * 增加音符币
     *
     * 乐观锁实现：
     * 1. 使用 where 条件同时检查 id 和 version
     * 2. 更新时同时增加 version
     * 3. 如果更新影响的行数为0，说明版本号已变化
     *
     * @param int $amount 增加的数量
     *
     * @throws \App\Modules\Wallet\Exceptions\ConcurrentModificationException 并发修改异常
     */
    public function incrementCoins(int $amount): void
    {
        $affected = self::where('id', $this->id)
            ->where('version', $this->version)
            ->update([
                'coins' => DB::raw("coins + {$amount}"),
                'version' => DB::raw('version + 1'),
            ]);

        if ($affected === 0) {
            throw new \App\Modules\Wallet\Exceptions\ConcurrentModificationException();
        }

        $this->refresh();
    }

    /**
     * 减少音符币
     *
     * 乐观锁实现：
     * 1. 使用 where 条件同时检查 id、version 和余额
     * 2. 更新时同时增加 version
     * 3. 如果更新影响的行数为0，可能是版本号变化或余额不足
     *
     * @param int $amount 减少的数量
     *
     * @throws \App\Modules\Wallet\Exceptions\InsufficientCoinsException      音符币不足异常
     * @throws \App\Modules\Wallet\Exceptions\ConcurrentModificationException 并发修改异常
     */
    public function decrementCoins(int $amount): void
    {
        $affected = self::where('id', $this->id)
            ->where('version', $this->version)
            ->where('coins', '>=', $amount)
            ->update([
                'coins' => DB::raw("coins - {$amount}"),
                'version' => DB::raw('version + 1'),
            ]);

        if ($affected === 0) {
            // 检查是否余额不足
            $current = self::find($this->id);
            if ($current && $current->coins < $amount) {
                throw new \App\Modules\Wallet\Exceptions\InsufficientCoinsException();
            }

            throw new \App\Modules\Wallet\Exceptions\ConcurrentModificationException();
        }

        $this->refresh();
    }

    /**
     * 检查音符币是否足够
     *
     * @param int $amount 需要的数量
     */
    public function hasEnoughCoins(int $amount): bool
    {
        return $this->coins >= $amount;
    }

    /**
     * 获取可用的音符币数量.
     */
    public function getAvailableCoins(): int
    {
        return max(0, $this->coins);
    }

    /**
     * 转换为人民币金额（1音符币 = 0.1元）.
     */
    public function toRmb(): float
    {
        return $this->coins * 0.1;
    }

    /**
     * 从人民币转换为音符币（1元 = 10音符币）.
     *
     * @param float $rmb 人民币金额
     */
    public static function fromRmb(float $rmb): int
    {
        return (int) ($rmb * 10);
    }
}
