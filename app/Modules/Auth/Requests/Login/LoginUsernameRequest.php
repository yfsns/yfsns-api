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

class LoginUsernameRequest extends BaseAuthRequest
{
    protected string $authType = 'login';
    protected string $methodType = 'username';

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
            'username' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\p{N}@._-]+$/u', 'exists:users,username'],
        ]);
    }

    /**
     * 获取验证错误的自定义消息
     */
    public function messages(): array
    {
        return array_merge($this->getBaseMessages(), [
            'username.required' => '请输入用户名',
            'username.max' => '用户名长度不能超过255个字符',
            'username.regex' => '用户名只能包含字母、数字、@、点、下划线和连字符',
            'username.exists' => '用户名不存在，请检查用户名或选择其他登录方式',
        ]);
    }

    /**
     * 获取验证错误的自定义属性
     */
    public function attributes(): array
    {
        return array_merge($this->getBaseAttributes(), [
            'username' => '用户名',
        ]);
    }
}
