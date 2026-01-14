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

namespace App\Modules\Wallet\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 虚拟币请求验证类
 *
 * 统一处理虚拟币相关的各种请求验证
 */
class VirtualCoinRequest extends FormRequest
{
    protected string $requestType = 'recharge';

    public function setRequestType(string $type): self
    {
        $this->requestType = $type;
        return $this;
    }

    public static function recharge(): self
    {
        return (new self())->setRequestType('recharge');
    }

    public static function consume(): self
    {
        return (new self())->setRequestType('consume');
    }

    public static function donate(): self
    {
        return (new self())->setRequestType('donate');
    }

    public static function reward(): self
    {
        return (new self())->setRequestType('reward');
    }

    public static function batchReward(): self
    {
        return (new self())->setRequestType('batch_reward');
    }

    public static function check(): self
    {
        return (new self())->setRequestType('check');
    }

    public static function history(): self
    {
        return (new self())->setRequestType('history');
    }

    public static function tipHistory(): self
    {
        return (new self())->setRequestType('tip_history');
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match ($this->requestType) {
            'recharge' => [
                'rmb_amount' => 'required|numeric|min:0.01|max:10000',
                'description' => 'nullable|string|max:255',
            ],
            'consume' => [
                'coins' => 'required|integer|min:1',
                'description' => 'required|string|max:255',
                'metadata' => 'nullable|array',
            ],
            'donate' => [
                'target_user_id' => 'required|integer|exists:users,id',
                'coins' => 'required|integer|min:1|max:10000',
                'description' => 'nullable|string|max:255',
                'metadata' => 'nullable|array',
            ],
            'reward' => [
                'user_id' => 'required|integer|exists:users,id',
                'coins' => 'required|integer|min:1',
                'description' => 'nullable|string|max:255',
                'metadata' => 'nullable|array',
            ],
            'batch_reward' => [
                'user_ids' => 'required|array|min:1|max:100',
                'user_ids.*' => 'integer|exists:users,id',
                'coins' => 'required|integer|min:1',
                'description' => 'nullable|string|max:255',
            ],
            'check' => [
                'required_coins' => 'required|integer|min:1',
            ],
            'history' => [
                'limit' => 'nullable|integer|min:1|max:100',
                'type' => 'nullable|string|in:recharge,tip,reward,consume',
            ],
            'tip_history' => [
                'limit' => 'nullable|integer|min:1|max:100',
                'type' => 'required|string|in:sent,received',
            ],
            default => [],
        };
    }

    protected function prepareForValidation(): void
    {
        if ($this->requestType === 'donate') {
            $this->merge([
                'target_user_id' => $this->targetUserId ?? $this->target_user_id,
            ]);
        }
    }
}
