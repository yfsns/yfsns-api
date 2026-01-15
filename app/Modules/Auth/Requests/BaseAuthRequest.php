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

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseAuthRequest extends FormRequest
{
    protected string $authType; // 'registration' 或 'login'
    protected string $methodType; // 'email', 'phone', 或 'username'

    /**
     * 确定用户是否有权限进行此请求
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 获取基础验证规则（密码、昵称等）
     */
    protected function getBaseRules(): array
    {
        $rules = [];

        // 密码验证（注册时需要）
        if ($this->authType === 'registration') {
            $rules['password'] = [
                'required',
                'confirmed',
                'min:6',
                app(\App\Modules\System\Services\PasswordValidationService::class)->getPasswordStrengthRule()
            ];
            $rules['password_confirmation'] = ['required', 'same:password'];
        }

        // 昵称验证（注册时需要）
        if ($this->authType === 'registration') {
            $rules['nickname'] = [
                'nullable',
                'string',
                'min:1',
                'max:50',
                'regex:/^[\p{L}\p{N}\s\-_]+$/u'
            ];
        }

        // 验证码验证（邮箱和手机号需要）
        if (in_array($this->methodType, ['email', 'phone'])) {
            $rules['verification_code'] = ['required', 'regex:/^[0-9]{6}$/'];
        }

        // 密码验证（登录时的用户名密码方式或统一密码登录）
        if ($this->authType === 'login' && ($this->methodType === 'username' || $this->methodType === 'password')) {
            $rules['password'] = ['required', 'string', 'min:6', 'max:255'];
        }

        return $rules;
    }

    /**
     * 获取基础错误消息
     */
    protected function getBaseMessages(): array
    {
        $messages = [
            'password.required' => '请输入密码',
            'password.min' => '密码至少6位',
            'password.confirmed' => '两次密码不一致',
            'password_confirmation.required' => '请确认密码',
            'password_confirmation.same' => '确认密码与密码不一致',
            'nickname.min' => '昵称至少1个字符',
            'nickname.max' => '昵称最多50个字符',
            'nickname.regex' => '昵称格式不正确',
            'verification_code.required' => '请输入验证码',
            'verification_code.regex' => '验证码必须是6位数字',
        ];

        if ($this->authType === 'login' && $this->methodType === 'username') {
            $messages = array_merge($messages, [
                'password.min' => '密码长度不能小于6个字符',
                'password.max' => '密码长度不能超过255个字符',
            ]);
        }

        return $messages;
    }

    /**
     * 获取基础属性名
     */
    protected function getBaseAttributes(): array
    {
        $attributes = [
            'password' => '密码',
            'password_confirmation' => '确认密码',
            'nickname' => '昵称（可选，不填则自动生成）',
            'verification_code' => '验证码',
        ];

        if ($this->authType === 'login' && $this->methodType === 'username') {
            $attributes['password'] = '密码';
        }

        return $attributes;
    }

    /**
     * 检查认证方式是否开启
     */
    protected function checkMethodEnabled(): array
    {
        $configService = app(\App\Modules\System\Services\ConfigService::class);

        $configKey = "enable_{$this->methodType}_{$this->authType}";
        $isEnabled = $configService->get($configKey, 'auth', true);

        if (!$isEnabled) {
            $methodNames = [
                'email' => $this->authType === 'registration' ? '邮箱注册' : '邮箱验证码登录',
                'phone' => $this->authType === 'registration' ? '手机号注册' : '手机号验证码登录',
                'username' => $this->authType === 'registration' ? '用户名注册' : '用户名密码登录',
            ];

            $errorField = $this->methodType;
            $errorMessage = "{$methodNames[$this->methodType]}功能暂未开启，请选择其他方式";

            return [
                'error_field' => $errorField,
                'error_message' => $errorMessage
            ];
        }

        return [];
    }

    /**
     * 准备验证数据
     */
    protected function prepareForValidation(): void
    {
        $data = [
            'nickname' => $this->nickname ?? $this->name,
            'verification_code' => $this->verificationCode ?? $this->verification_code,
        ];

        if ($this->authType === 'registration') {
            $data['password_confirmation'] = $this->passwordConfirmation ?? $this->password_confirmation;
        }

        $this->merge($data);
    }
}
