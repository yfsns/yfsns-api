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

namespace App\Modules\Auth\Requests\Login;

use App\Modules\Auth\Requests\BaseAuthRequest;
use Illuminate\Validation\Rule;

class LoginPasswordRequest extends BaseAuthRequest
{
    protected string $authType = 'login';
    protected string $methodType = 'password';

    /**
     * 获取应用于请求的验证规则
     */
    public function rules(): array
    {
        $methodCheck = $this->checkMethodEnabled();
        if (!empty($methodCheck)) {
            return [
                $methodCheck['error_field'] => [
                    'bail',
                    'required',
                    function ($attribute, $value, $fail) use ($methodCheck) {
                        $fail($methodCheck['error_message']);
                    }
                ]
            ];
        }

        return array_merge($this->getBaseRules(), [
            'account' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    // 验证账号格式：用户名、邮箱或手机号
                    $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL);
                    $isPhone = preg_match('/^1[3-9]\d{9}$/', $value);
                    $isUsername = preg_match('/^[\p{L}\p{N}@._-]+$/u', $value);

                    if (!$isEmail && !$isPhone && !$isUsername) {
                        $fail('账号格式不正确，请输入用户名、邮箱或手机号');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ]);
    }

    /**
     * 获取验证错误的自定义消息
     */
    public function messages(): array
    {
        return array_merge($this->getBaseMessages(), [
            'account.required' => '请输入账号',
            'account.max' => '账号长度不能超过255个字符',
            'password.required' => '请输入密码',
            'password.min' => '密码长度不能小于6个字符',
            'password.max' => '密码长度不能超过255个字符',
        ]);
    }

    /**
     * 获取验证错误的自定义属性
     */
    public function attributes(): array
    {
        return array_merge($this->getBaseAttributes(), [
            'account' => '账号',
            'password' => '密码',
        ]);
    }
}
