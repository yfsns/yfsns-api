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

use App\Modules\Wallet\Exceptions\ConcurrentModificationException;
use App\Modules\Wallet\Exceptions\InsufficientBalanceException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * 余额模型
 *
 * 管理用户的余额数据，提供安全的余额增减操作和并发控制
 */
class Balance extends Model
{
    /**
     * 关联的表名.
     */
    protected $table = 'wallet_balances';

    /**
     * 可批量赋值的属性.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',    // 用户ID
        'balance',    // 余额
        'version',     // 版本号，用于乐观锁
    ];

    /**
     * 属性类型转换.
     *
     * @var array
     */
    protected $casts = [
        'balance' => 'decimal:2',  // 余额保留2位小数
        'version' => 'integer',     // 版本号为整数
    ];

    /**
     * 关联用户模型.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class);
    }

    /**
     * 增加余额.
     *
     * 乐观锁实现：
     * 1. 使用 where 条件同时检查 id 和 version
     * 2. 更新时同时增加 version
     * 3. 如果更新影响的行数为0，说明版本号已变化
     *
     * @param float $amount 增加的金额
     *
     * @throws ConcurrentModificationException 并发修改异常
     */
    public function incrementBalance($amount): void
    {
        $affected = self::where('id', $this->id)
            ->where('version', $this->version)
            ->update([
                'balance' => DB::raw("balance + {$amount}"),
                'version' => DB::raw('version + 1'),
            ]);

        if ($affected === 0) {
            throw new ConcurrentModificationException();
        }

        $this->refresh();
    }

    /**
     * 减少余额.
     *
     * 乐观锁实现：
     * 1. 使用 where 条件同时检查 id、version 和余额
     * 2. 更新时同时增加 version
     * 3. 如果更新影响的行数为0，可能是版本号变化或余额不足
     *
     * @param float $amount 减少的金额
     *
     * @throws InsufficientBalanceException    余额不足异常
     * @throws ConcurrentModificationException 并发修改异常
     */
    public function decrementBalance($amount): void
    {
        $affected = self::where('id', $this->id)
            ->where('version', $this->version)
            ->where('balance', '>=', $amount)
            ->update([
                'balance' => DB::raw("balance - {$amount}"),
                'version' => DB::raw('version + 1'),
            ]);

        if ($affected === 0) {
            // 检查是否余额不足
            $current = self::find($this->id);
            if ($current && $current->balance < $amount) {
                throw new InsufficientBalanceException();
            }

            throw new ConcurrentModificationException();
        }

        $this->refresh();
    }

    /**
     * 检查余额是否足够
     *
     * @param float $amount 需要的金额
     */
    public function hasEnoughBalance($amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * 获取可用余额.
     */
    public function getAvailableBalance(): float
    {
        return max(0, $this->balance);
    }

    /**
     * 获取余额信息.
     */
    public function getBalanceInfo(): array
    {
        return [
            'balance' => $this->balance,
            'user_id' => $this->user_id,
            'formatted_balance' => number_format($this->balance, 2),
        ];
    }
}
