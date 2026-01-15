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
 * 交易记录资源类.
 *
 * 格式化交易记录数据为驼峰格式
 */
class TransactionResource extends JsonResource
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
            'status' => $this->status,
            'createdAt' => $this->created_at?->toIso8601String(),
            'createdAtHuman' => $this->created_at?->diffForHumans(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
