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
 * 余额资源类.
 *
 * 格式化余额数据为驼峰格式
 */
class BalanceResource extends JsonResource
{
    public function toArray($request)
    {
        // balances表只有: id, user_id, balance, version
        // frozen_balance等字段通过计算获取
        return [
            'id' => (string) $this->id,
            'userId' => (string) $this->user_id,
            'balance' => (float) $this->balance,
            'frozenBalance' => 0, // balances表没有此字段，默认0
            'availableBalance' => (float) $this->balance,
            'totalRecharge' => 0, // 需要从balance_transactions计算
            'totalConsume' => 0, // 需要从balance_transactions计算
            'version' => $this->version ?? 0,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
