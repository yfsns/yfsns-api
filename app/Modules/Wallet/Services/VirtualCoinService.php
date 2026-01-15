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

use App\Modules\Wallet\Exceptions\InsufficientCoinsException;
use App\Modules\Wallet\Models\CoinTransaction;
use App\Modules\Wallet\Models\VirtualCoin;

use function count;

use Illuminate\Support\Facades\DB;

/**
 * 虚拟币服务类
 *
 * 处理虚拟币的充值、消费、奖励等操作，支持交易记录管理
 */
class VirtualCoinService
{
    /**
     * 获取用户虚拟币账户
     *
     * @param int $userId 用户ID
     */
    public function getCoinAccount(int $userId): VirtualCoin
    {
        return VirtualCoin::firstOrCreate(['user_id' => $userId]);
    }

    /**
     * 充值虚拟币
     *
     * @param int    $userId      用户ID
     * @param float  $rmbAmount   人民币金额
     * @param string $description 描述
     *
     * @return array 充值结果
     */
    public function recharge(int $userId, float $rmbAmount, string $description = '充值虚拟币'): array
    {
        DB::transaction(function () use ($userId, $rmbAmount, $description): void {
            // 计算虚拟币数量（1元 = 10虚拟币）
            $coins = VirtualCoin::fromRmb($rmbAmount);

            // 更新用户虚拟币余额
            $account = $this->getCoinAccount($userId);
            $account->incrementCoins($coins);

            // 创建充值记录
            CoinTransaction::createRecharge($userId, $coins, $rmbAmount, $description);
        });

        $account = $this->getCoinAccount($userId);

        return [
            'success' => true,
            'coins' => $account->coins,
            'rmb_amount' => $rmbAmount,
            'message' => "充值成功，获得 {$account->coins} 虚拟币",
        ];
    }


    /**
     * 奖励虚拟币
     *
     * @param int    $userId      用户ID
     * @param int    $coins       虚拟币数量
     * @param string $description 描述
     * @param array  $metadata    元数据
     *
     * @return array 奖励结果
     */
    public function rewardCoins(int $userId, int $coins, string $description = '获得奖励', array $metadata = []): array
    {
        DB::transaction(function () use ($userId, $coins, $description, $metadata): void {
            // 更新用户虚拟币余额
            $account = $this->getCoinAccount($userId);
            $account->incrementCoins($coins);

            // 创建奖励记录
            CoinTransaction::createReward($userId, $coins, $description, $metadata);
        });

        $account = $this->getCoinAccount($userId);

        return [
            'success' => true,
            'coins' => $coins,
            'total_coins' => $account->coins,
            'message' => "奖励成功，获得 {$coins} 虚拟币",
        ];
    }

    /**
     * 消费虚拟币
     *
     * @param int    $userId      用户ID
     * @param int    $coins       虚拟币数量
     * @param string $description 描述
     * @param array  $metadata    元数据
     *
     * @return array 消费结果
     */
    public function consumeCoins(int $userId, int $coins, string $description = '消费虚拟币', array $metadata = []): array
    {
        // 检查余额是否足够
        $account = $this->getCoinAccount($userId);
        if (! $account->hasEnoughCoins($coins)) {
            throw new InsufficientCoinsException();
        }

        DB::transaction(function () use ($userId, $coins, $description, $metadata): void {
            // 减少虚拟币
            $account = $this->getCoinAccount($userId);
            $account->decrementCoins($coins);

            // 创建消费记录
            CoinTransaction::createConsume($userId, $coins, $description, $metadata);
        });

        $account = $this->getCoinAccount($userId);

        return [
            'success' => true,
            'coins' => $coins,
            'remaining_coins' => $account->coins,
            'message' => "消费成功，使用 {$coins} 虚拟币",
        ];
    }

    /**
     * 获取用户虚拟币统计
     *
     * @param int $userId 用户ID
     *
     * @return array 统计信息
     */
    public function getUserCoinStats(int $userId): array
    {
        $account = $this->getCoinAccount($userId);

        $totalRecharge = CoinTransaction::where('user_id', $userId)
            ->where('type', 'recharge')
            ->sum('rmb_amount');

        $totalTips = abs(CoinTransaction::where('user_id', $userId)
            ->where('type', 'tip')
            ->where('coins', '<', 0)
            ->sum('rmb_amount'));

        $totalReceived = CoinTransaction::where('user_id', $userId)
            ->where('type', 'tip')
            ->where('coins', '>', 0)
            ->sum('rmb_amount');

        $totalRewards = CoinTransaction::where('user_id', $userId)
            ->where('type', 'reward')
            ->sum('rmb_amount');

        $totalConsume = abs(CoinTransaction::where('user_id', $userId)
            ->where('type', 'consume')
            ->sum('rmb_amount'));

        return [
            'current_coins' => $account->coins,
            'current_rmb_value' => $account->toRmb(),
            'total_recharge' => $totalRecharge,
            'total_tips_sent' => $totalTips,
            'total_tips_received' => $totalReceived,
            'total_rewards' => $totalRewards,
            'total_consume' => $totalConsume,
            'net_worth' => $account->toRmb() + $totalReceived + $totalRewards - $totalTips - $totalConsume,
        ];
    }

    /**
     * 获取虚拟币排行榜
     *
     * @param int $limit 限制数量
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCoinLeaderboard(int $limit = 20)
    {
        return VirtualCoin::with('user:' . \App\Modules\User\Models\User::BASIC_FIELDS)
            ->orderBy('coins', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取用户虚拟币历史记录
     *
     * @param int $userId 用户ID
     * @param int $limit  限制数量
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserCoinHistory(int $userId, int $limit = 50)
    {
        return CoinTransaction::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 检查虚拟币是否足够
     *
     * @param int $userId        用户ID
     * @param int $requiredCoins 需要的虚拟币数量
     */
    public function hasEnoughCoins(int $userId, int $requiredCoins): bool
    {
        $account = $this->getCoinAccount($userId);

        return $account->hasEnoughCoins($requiredCoins);
    }

    /**
     * 批量奖励虚拟币
     *
     * @param array  $userIds     用户ID数组
     * @param int    $coins       虚拟币数量
     * @param string $description 描述
     *
     * @return array 奖励结果
     */
    public function batchRewardCoins(array $userIds, int $coins, string $description = '批量奖励'): array
    {
        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($userIds as $userId) {
            $result = $this->rewardCoins($userId, $coins, $description, [
                'batch_id' => uniqid(),
                'description' => $description,
            ]);

            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }

            $results[] = $result;
        }

        return [
            'total' => count($userIds),
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'results' => $results,
        ];
    }
}
