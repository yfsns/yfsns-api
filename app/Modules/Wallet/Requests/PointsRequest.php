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
 * 积分请求验证类
 *
 * 统一处理积分相关的各种请求验证
 */
class PointsRequest extends FormRequest
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

    public static function add(): self
    {
        return (new self())->setRequestType('add');
    }

    public static function batchAdd(): self
    {
        return (new self())->setRequestType('batch_add');
    }

    public static function check(): self
    {
        return (new self())->setRequestType('check');
    }

    public static function history(): self
    {
        return (new self())->setRequestType('history');
    }

    public static function trigger(): self
    {
        return (new self())->setRequestType('trigger');
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match ($this->requestType) {
            'use' => [
                'points' => 'required|integer|min:1',
                'description' => 'nullable|string|max:255',
            ],
            'add' => [
                'user_id' => 'required|integer|exists:users,id',
                'points' => 'required|integer|min:1',
                'rule_id' => 'nullable|integer|exists:wallet_points_rules,id',
                'description' => 'nullable|string|max:255',
            ],
            'batch_add' => [
                'user_ids' => 'required|array|min:1|max:100',
                'user_ids.*' => 'integer|exists:users,id',
                'points' => 'required|integer|min:1',
                'description' => 'nullable|string|max:255',
            ],
            'check' => [
                'required_points' => 'required|integer|min:1',
            ],
            'history' => [
                'limit' => 'nullable|integer|min:1|max:100',
            ],
            'trigger' => [
                'rule_id' => 'required|integer|exists:wallet_points_rules,id',
                'context' => 'nullable|array',
            ],
            default => [],
        };
    }

    protected function prepareForValidation(): void
    {
        if ($this->requestType === 'check') {
            $this->merge([
                'required_points' => $this->requiredPoints ?? $this->required_points,
            ]);
        }
    }
}
