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

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'old_password' => [
                'required',
                'string',
                'min:6',
                'max:255'
            ],
            'new_password' => [
                'required',
                'string',
                'different:old_password', // 新密码不能与旧密码相同
                'min:6',
                'max:255',
                app(\App\Modules\System\Services\PasswordValidationService::class)->getPasswordStrengthRule()
            ],
            'confirm_password' => [
                'required',
                'string',
                'same:new_password', // 必须与新密码完全一致
                'min:6',
                'max:255'
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'old_password' => '原密码',
            'new_password' => '新密码',
            'confirm_password' => '确认密码',
        ];
    }

    public function messages(): array
    {
        return [
            'old_password.required' => '请输入原密码',
            'old_password.min' => '原密码长度不能小于6个字符',
            'old_password.max' => '原密码长度不能超过255个字符',
            'new_password.required' => '请输入新密码',
            'new_password.min' => '新密码长度不能小于6个字符',
            'new_password.max' => '新密码长度不能超过255个字符',
            'new_password.different' => '新密码不能与原密码相同',
            'confirm_password.required' => '请输入确认密码',
            'confirm_password.min' => '确认密码长度不能小于6个字符',
            'confirm_password.max' => '确认密码长度不能超过255个字符',
            'confirm_password.same' => '确认密码与新密码不一致',
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
            'old_password' => $this->oldPassword ?? $this->old_password,
            'new_password' => $this->newPassword ?? $this->new_password,
            'confirm_password' => $this->confirmPassword ?? $this->confirm_password,
        ]);
    }
}
