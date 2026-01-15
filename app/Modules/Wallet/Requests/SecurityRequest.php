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
 * 安全请求验证类
 *
 * 统一处理钱包安全相关的各种请求验证
 */
class SecurityRequest extends FormRequest
{
    protected string $requestType = 'update';

    public function setRequestType(string $type): self
    {
        $this->requestType = $type;
        return $this;
    }

    public static function update(): self
    {
        return (new self())->setRequestType('update');
    }

    public static function setPassword(): self
    {
        return (new self())->setRequestType('set_password');
    }

    public static function verifyPassword(): self
    {
        return (new self())->setRequestType('verify_password');
    }

    public static function checkLimit(): self
    {
        return (new self())->setRequestType('check_limit');
    }

    public static function logs(): self
    {
        return (new self())->setRequestType('logs');
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match ($this->requestType) {
            'update' => [
                'transaction_limit' => 'nullable|numeric|min:0|max:100000',
                'daily_limit' => 'nullable|numeric|min:0|max:1000000',
                'require_password' => 'nullable|boolean',
                'allow_biometric' => 'nullable|boolean',
                'notification_enabled' => 'nullable|boolean',
            ],
            'set_password' => [
                'password' => 'required|string|min:6|max:32',
                'confirm_password' => 'required|string|same:password',
            ],
            'verify_password' => [
                'password' => 'required|string',
            ],
            'check_limit' => [
                'amount' => 'required|numeric|min:0.01',
                'type' => 'required|string|in:transfer,payment',
            ],
            'logs' => [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'type' => 'nullable|string|in:login,transaction,password_change,security_alert',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ],
            default => [],
        };
    }

    protected function prepareForValidation(): void
    {
        if ($this->requestType === 'set_password') {
            $this->merge([
                'confirm_password' => $this->confirmPassword ?? $this->confirm_password,
            ]);
        }
    }
}
