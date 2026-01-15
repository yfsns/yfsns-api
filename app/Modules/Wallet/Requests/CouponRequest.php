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
 * 优惠券请求验证类
 *
 * 统一处理优惠券相关的各种请求验证
 */
class CouponRequest extends FormRequest
{
    protected string $requestType = 'use';

    public function setRequestType(string $type): self
    {
        $this->requestType = $type;
        return $this;
    }

    public static function use(): self
    {
        return (new self())->setRequestType('use');
    }

    public static function forCreate(): self
    {
        return (new self())->setRequestType('create');
    }

    public static function issue(): self
    {
        return (new self())->setRequestType('issue');
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match ($this->requestType) {
            'use' => [
                'coupon_id' => 'required|integer|exists:wallet_coupons,coupon_id',
            ],
            'create' => [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'type' => 'required|in:discount,cash,free_shipping',
                'discount_type' => 'required_if:type,discount|in:fixed,percentage',
                'discount_value' => 'required|numeric|min:0.01',
                'min_amount' => 'nullable|numeric|min:0',
                'max_discount' => 'nullable|numeric|min:0',
                'total_quantity' => 'required|integer|min:1',
                'start_time' => 'required|date|after:now',
                'end_time' => 'required|date|after:start_time',
                'rules' => 'nullable|array',
            ],
            'issue' => [
                'coupon_template_id' => 'required|integer|exists:wallet_coupon_templates,id',
                'user_ids' => 'required|array|min:1|max:100',
                'user_ids.*' => 'integer|exists:users,id',
            ],
            default => [],
        };
    }

    protected function prepareForValidation(): void
    {
        if ($this->requestType === 'use') {
            $this->merge([
                'coupon_id' => $this->couponId ?? $this->coupon_id,
            ]);
        } elseif ($this->requestType === 'issue') {
            $this->merge([
                'coupon_template_id' => $this->couponTemplateId ?? $this->coupon_template_id,
            ]);
        }
    }
}
