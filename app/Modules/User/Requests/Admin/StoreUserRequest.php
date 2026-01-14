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

namespace App\Modules\User\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'unique:users,username',
                'regex:/^[a-zA-Z][a-zA-Z0-9_]+$/u' // 以字母开头，只能包含字母、数字、下划线
            ],
            'password' => [
                'required',
                'string',
                'min:6',
                'max:255',
                app(\App\Modules\System\Services\PasswordValidationService::class)->getPasswordStrengthRule()
            ],
            'nickname' => [
                'sometimes',
                'string',
                'min:1',
                'max:50',
                'regex:/^[\p{L}\p{N}\s\-_]+$/u' // 允许字母、数字、空格、连字符、下划线
            ],
            'email' => [
                'required',
                'email:rfc,dns', // 增强的邮箱验证
                'max:255',
                'unique:users,email'
            ],
            'status' => [
                'sometimes',
                'integer',
                'in:0,1' // 0=禁用，1=启用
            ],
            'role_id' => [
                'sometimes',
                'integer',
                'exists:user_roles,id' // 修正表名，应该是user_roles而不是user_groups
            ],
        ];
    }

    /**
     * 获取验证错误的自定义属性
     */
    public function attributes(): array
    {
        return [
            'username' => '用户名',
            'password' => '密码',
            'nickname' => '昵称',
            'email' => '邮箱地址',
            'status' => '用户状态',
            'role_id' => '用户角色',
        ];
    }

    /**
     * 获取验证错误的自定义消息
     */
    public function messages(): array
    {
        return [
            'username.required' => '请输入用户名',
            'username.min' => '用户名长度不能小于3个字符',
            'username.max' => '用户名长度不能超过50个字符',
            'username.unique' => '该用户名已被使用',
            'username.regex' => '用户名必须以字母开头，只能包含字母、数字和下划线',
            'password.required' => '请输入密码',
            'password.min' => '密码长度不能小于6个字符',
            'password.max' => '密码长度不能超过255个字符',
            'nickname.min' => '昵称长度不能小于1个字符',
            'nickname.max' => '昵称长度不能超过50个字符',
            'nickname.regex' => '昵称只能包含字母、数字、空格、连字符和下划线',
            'email.required' => '请输入邮箱地址',
            'email.email' => '邮箱格式不正确',
            'email.max' => '邮箱长度不能超过255个字符',
            'email.unique' => '该邮箱已被注册',
            'status.in' => '用户状态只能是：0（禁用）或1（启用）',
            'role_id.exists' => '选择的角色不存在',
        ];
    }

    /**
     * 获取每个参数的详细说明（用于接口文档）
     */
    public function bodyParameters(): array
    {
        return [
            'username' => [
                'description' => '用户登录名，以字母开头，只能包含字母、数字、下划线，3-50字符。',
                'example' => 'user123',
            ],
            'password' => [
                'description' => '用户密码，密码强度根据系统配置要求（弱/中/强），最少6位字符。',
                'example' => 'Abc123!@#',
            ],
            'nickname' => [
                'description' => '用户昵称，支持中文、英文、数字、空格、连字符和下划线，1-50字符。',
                'example' => '张三',
            ],
            'email' => [
                'description' => '用户邮箱地址，支持RFC和DNS验证，必须唯一且未被注册。',
                'example' => 'user@example.com',
            ],
            'status' => [
                'description' => '用户状态，0=禁用，1=启用，默认启用。',
                'example' => 1,
            ],
            'role_id' => [
                'description' => '用户角色ID，对应user_roles表中的角色。',
                'example' => 2,
            ],
        ];
    }

    /**
     * 准备验证数据.
     * 转换前端驼峰格式字段为后端下划线格式.
     */
    protected function prepareForValidation(): void
    {
        // 转换驼峰格式字段为下划线格式
        $this->merge([
            'role_id' => $this->roleId ?? $this->role_id,
        ]);
    }
}
