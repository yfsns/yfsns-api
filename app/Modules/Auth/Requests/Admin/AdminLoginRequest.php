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

namespace App\Modules\Auth\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 管理员登录请求验证
 */
class AdminLoginRequest extends FormRequest
{
    /**
     * 确定用户是否有权限进行此请求
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 获取应用于请求的验证规则
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ];
    }

    /**
     * 获取验证错误的自定义属性
     */
    public function attributes(): array
    {
        return [
            'username' => '管理员用户名',
            'password' => '管理员密码',
        ];
    }

    /**
     * 获取验证错误的自定义消息
     */
    public function messages(): array
    {
        return [
            'username.required' => '请输入管理员用户名',
            'username.max' => '用户名长度不能超过50个字符',
            'password.required' => '请输入管理员密码',
            'password.min' => '密码长度不能小于6个字符',
            'password.max' => '密码长度不能超过255个字符',
        ];
    }

    /**
     * 获取每个参数的详细说明（用于接口文档）
     */
    public function bodyParameters(): array
    {
        return [
            'username' => [
                'description' => '管理员用户名，用于系统管理登录。',
                'example' => 'admin',
            ],
            'password' => [
                'description' => '管理员密码，至少6位字符。',
                'example' => 'admin123',
            ],
        ];
    }
}
