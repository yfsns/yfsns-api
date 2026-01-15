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
 * 余额请求验证类
 *
 * 统一处理余额相关的各种请求验证
 */
class BalanceRequest extends FormRequest
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

    public static function transactions(): self
    {
        return (new self())->setRequestType('transactions');
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match ($this->requestType) {
            'recharge' => [
                'amount' => 'required|numeric|min:0.01|max:50000',
                'payment_method' => 'required|string|in:alipay,wechat,bank_card',
                'description' => 'nullable|string|max:255',
            ],
            'consume' => [
                'amount' => 'required|numeric|min:0.01',
                'description' => 'required|string|max:255',
                'metadata' => 'nullable|array',
            ],
            'transactions' => [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'type' => 'nullable|string|in:recharge,consume,refund,transfer_in,transfer_out',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ],
            default => [],
        };
    }
}
