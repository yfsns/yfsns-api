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

namespace App\Modules\Wallet\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 虚拟币资源类
 *
 * 统一处理虚拟币相关的各种数据格式化，包括账户、统计和排行榜数据
 */
class VirtualCoinResource extends JsonResource
{
    /**
     * 数据类型标识
     */
    protected string $resourceType = 'account';

    /**
     * 设置资源类型
     */
    public function setResourceType(string $type): self
    {
        $this->resourceType = $type;
        return $this;
    }

    /**
     * 创建排行榜资源
     */
    public static function leaderboard($resource): self
    {
        return (new self($resource))->setResourceType('leaderboard');
    }

    /**
     * 创建统计资源
     */
    public static function stats($resource): self
    {
        return (new self($resource))->setResourceType('stats');
    }

    public function toArray($request)
    {
        return match ($this->resourceType) {
            'leaderboard' => $this->formatLeaderboard(),
            'stats' => $this->formatStats(),
            default => $this->formatAccount(),
        };
    }

    /**
     * 格式化账户数据
     */
    protected function formatAccount(): array
    {
        return [
            'id' => (string) $this->id,
            'userId' => (string) $this->user_id,
            'balance' => $this->balance ?? 0,
            'frozenBalance' => $this->frozen_balance ?? 0,
            'availableBalance' => ($this->balance ?? 0) - ($this->frozen_balance ?? 0),
            'version' => $this->version ?? 0,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * 格式化排行榜数据
     */
    protected function formatLeaderboard(): array
    {
        return [
            'userId' => (string) $this->user_id,
            'totalCoins' => (string) $this->total_coins,
            'user' => [
                'id' => (string) $this->user->id,
                'username' => $this->user->username,
                'nickname' => $this->user->nickname,
                'avatarUrl' => $this->user->avatar_url,
            ],
        ];
    }

    /**
     * 格式化统计数据
     */
    protected function formatStats(): array
    {
        return [
            'currentCoins' => $this->resource['current_coins'] ?? 0,
            'totalRecharge' => $this->resource['total_recharge'] ?? 0,
            'totalConsume' => $this->resource['total_consume'] ?? 0,
            'totalTip' => $this->resource['total_tip'] ?? 0,
            'totalReceived' => $this->resource['total_received'] ?? 0,
            'rmbValue' => $this->resource['rmb_value'] ?? 0,
        ];
    }
}
