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

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BindEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // 获取当前用户ID，排除自己的邮箱（支持换绑）
        $userId = $this->user()?->id;

        return [
            'email' => [
                'required',
                'string',
                'email:rfc,dns', // 增强的邮箱验证
                'max:255',
                'unique:users,email,' . $userId // 排除当前用户的邮箱
            ],
            'code' => [
                'required',
                'string',
                'regex:/^[0-9]{6}$/', // 确保是6位数字
                'size:6'
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => '邮箱',
            'code' => '验证码',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => '请输入邮箱地址',
            'email.email' => '邮箱格式不正确',
            'email.max' => '邮箱长度不能超过255个字符',
            'email.unique' => '该邮箱已被其他用户绑定',
            'code.required' => '请输入验证码',
            'code.regex' => '验证码必须是6位数字',
            'code.size' => '验证码长度必须为6位',
        ];
    }

    /**
     * 获取每个参数的详细说明（用于接口文档）
     */
    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => '要绑定的邮箱地址，支持RFC和DNS验证，不能与其他用户重复。',
                'example' => 'user@example.com',
            ],
            'code' => [
                'description' => '邮箱验证码，6位数字，用于验证邮箱所有权。',
                'example' => '123456',
            ],
        ];
    }
}
