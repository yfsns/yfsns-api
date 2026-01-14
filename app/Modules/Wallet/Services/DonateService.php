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
use Illuminate\Support\Facades\DB;

/**
 * 打赏服务类
 *
 * 处理用户之间的虚拟币打赏功能，包括打赏操作和打赏记录查询
 */
class DonateService
{
    /**
     * 打赏用户
     *
     * @param int    $fromUserId  打赏用户ID
     * @param int    $toUserId    被打赏用户ID
     * @param int    $coins       虚拟币数量
     * @param string $description 描述
     * @param array  $metadata    元数据
     *
     * @return array 打赏结果
     */
    public function donateToUser(int $fromUserId, int $toUserId, int $coins, string $description = 'Donate', array $metadata = []): array
    {
        // 检查打赏者是否有足够的虚拟币
        $fromAccount = $this->getCoinAccount($fromUserId);
        if (! $fromAccount->hasEnoughCoins($coins)) {
            throw new InsufficientCoinsException();
        }

        DB::transaction(function () use ($fromUserId, $toUserId, $coins, $description, $metadata): void {
            // 打赏者减少虚拟币
            $fromAccount = $this->getCoinAccount($fromUserId);
            $fromAccount->decrementCoins($coins);

            // 被打赏者增加虚拟币
            $toAccount = $this->getCoinAccount($toUserId);
            $toAccount->incrementCoins($coins);

            // 创建打赏记录（打赏者）
            CoinTransaction::createTip($fromUserId, $toUserId, $coins, $description, $metadata);

            // 创建被打赏记录（被打赏者）
            CoinTransaction::createReceivedTip($fromUserId, $toUserId, $coins, $description, $metadata);
        });

        return [
            'success' => true,
            'coins' => $coins,
            'rmb_amount' => $coins * 0.1,
            'message' => "Donate successful, sent {$coins} coins",
        ];
    }

    /**
     * 获取用户打赏历史
     *
     * @param int $userId 用户ID
     * @param int $limit  限制数量
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserDonateHistory(int $userId, int $limit = 50)
    {
        return CoinTransaction::where('user_id', $userId)
            ->where('type', 'tip')
            ->with('targetUser:id,username,nickname,avatar')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取用户发送的打赏记录
     *
     * @param int $userId 用户ID
     * @param int $limit  限制数量
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSentDonates(int $userId, int $limit = 50)
    {
        return CoinTransaction::where('user_id', $userId)
            ->where('type', 'tip')
            ->where('coins', '<', 0)
            ->with('targetUser:id,username,nickname,avatar')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取用户收到的打赏记录
     *
     * @param int $userId 用户ID
     * @param int $limit  限制数量
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getReceivedDonates(int $userId, int $limit = 50)
    {
        return CoinTransaction::where('user_id', $userId)
            ->where('type', 'tip')
            ->where('coins', '>', 0)
            ->with(['targetUser:id,username,nickname,avatar'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取用户的虚拟币账户
     *
     * @param int $userId 用户ID
     *
     * @return VirtualCoin
     */
    private function getCoinAccount(int $userId): VirtualCoin
    {
        return VirtualCoin::firstOrCreate(['user_id' => $userId]);
    }
}
