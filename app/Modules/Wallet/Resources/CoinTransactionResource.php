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
 * 金币交易记录资源类.
 *
 * 格式化金币交易记录为驼峰格式
 */
class CoinTransactionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (string) $this->id,
            'userId' => (string) $this->user_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'balanceBefore' => $this->balance_before,
            'balanceAfter' => $this->balance_after,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'targetUserId' => $this->target_user_id ? (string) $this->target_user_id : null,
            'relatedType' => $this->related_type,
            'relatedId' => $this->related_id ? (string) $this->related_id : null,
            'status' => $this->status,

            // 关联用户信息
            'targetUser' => $this->whenLoaded('targetUser', function () {
                return [
                    'id' => (string) $this->targetUser->id,
                    'username' => $this->targetUser->username,
                    'nickname' => $this->targetUser->nickname,
                    'avatarUrl' => $this->targetUser->avatar_url,
                ];
            }),

            'createdAt' => $this->created_at?->toIso8601String(),
            'createdAtHuman' => $this->created_at?->diffForHumans(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
