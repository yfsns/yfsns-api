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

namespace App\Modules\Wallet\Services;

use App\Modules\Wallet\Exceptions\InsufficientBalanceException;
use App\Modules\Wallet\Models\Balance;
use App\Modules\Wallet\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * 余额服务类
 *
 * 处理用户的余额充值、消费等交易操作，确保数据一致性
 */
class BalanceService
{
    /**
     * 获取用户余额账户.
     *
     * 说明：
     * - 如果用户没有余额账户，会自动创建一个
     * - 使用 firstOrCreate 确保余额账户的唯一性
     *
     * @param int $userId 用户ID
     */
    public function getBalance($userId): Balance
    {
        return Balance::firstOrCreate(['user_id' => $userId]);
    }

    /**
     * 充值
     *
     * 事务说明：
     * - 使用事务确保余额增加和交易记录创建的原子性
     * - 如果任何操作失败，整个充值过程会回滚
     *
     * @param int    $userId      用户ID
     * @param float  $amount      充值金额
     * @param string $description 描述
     */
    public function recharge($userId, $amount, $description = '充值'): Balance
    {
        return DB::transaction(function () use ($userId, $amount, $description) {
            $balance = $this->getBalance($userId);
            $balance->incrementBalance($amount);

            Transaction::createRecharge($userId, $amount, $description);

            return $balance;
        });
    }

    /**
     * 消费.
     *
     * 事务说明：
     * - 使用事务确保余额减少和交易记录创建的原子性
     * - 如果任何操作失败，整个消费过程会回滚
     *
     * @param int    $userId      用户ID
     * @param float  $amount      消费金额
     * @param string $description 描述
     *
     * @throws InsufficientBalanceException 余额不足异常
     */
    public function consume($userId, $amount, $description = '消费'): Balance
    {
        return DB::transaction(function () use ($userId, $amount, $description) {
            $balance = $this->getBalance($userId);
            $balance->decrementBalance($amount);

            Transaction::createConsume($userId, $amount, $description);

            return $balance;
        });
    }

    /**
     * 获取余额信息.
     *
     * @param int $userId 用户ID
     */
    public function getBalanceInfo($userId): array
    {
        $balance = $this->getBalance($userId);

        return $balance->getBalanceInfo();
    }

    /**
     * 检查余额是否足够
     *
     * @param int   $userId 用户ID
     * @param float $amount 需要的金额
     */
    public function hasEnoughBalance($userId, $amount): bool
    {
        $balance = $this->getBalance($userId);

        return $balance->hasEnoughBalance($amount);
    }

    /**
     * 获取用户余额统计
     *
     * @param int $userId 用户ID
     */
    public function getBalanceStats($userId): array
    {
        $balance = $this->getBalance($userId);

        return [
            'current_balance' => $balance->balance,
            'formatted_balance' => number_format($balance->balance, 2),
            'total_recharge' => Transaction::where('user_id', $userId)
                ->where('type', 'recharge')
                ->sum('amount'),
            'total_consume' => abs(Transaction::where('user_id', $userId)
                ->where('type', 'consume')
                ->sum('amount')),
            'today_recharge' => Transaction::where('user_id', $userId)
                ->where('type', 'recharge')
                ->whereDate('created_at', today())
                ->sum('amount'),
            'this_month_recharge' => Transaction::where('user_id', $userId)
                ->where('type', 'recharge')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
        ];
    }
}
