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

namespace App\Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use const FILTER_VALIDATE_BOOLEAN;

class EmailConfigRequest extends FormRequest
{
    /**
     * 确定用户是否有权限进行此请求
     */
    public function authorize(): bool
    {
        return true; // 或者根据你的权限逻辑返回 true/false
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'nullable|string|max:255',
            'driver' => 'nullable|string|in:smtp,ses,mailgun,log',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'encryption' => 'nullable|string|in:tls,ssl',
            'username' => 'required|string|max:255',
            'from_address' => 'required|email|max:255',
            'from_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'status' => 'nullable|boolean',
        ];

        // 邮件配置更新：密码可以为空（保留原密码）
        // 因为邮件配置只有一个配置项，POST 请求用于更新，密码为空时保留原密码
        $rules['password'] = 'nullable|string|max:255';

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'name' => '配置名称',
            'driver' => '邮件驱动',
            'host' => 'SMTP主机',
            'port' => 'SMTP端口',
            'encryption' => '加密方式',
            'username' => '用户名',
            'password' => '密码',
            'from_address' => '发件人邮箱',
            'from_name' => '发件人名称',
            'description' => '配置描述',
            'status' => '状态',
        ];
    }

    public function messages(): array
    {
        return [
            'host.required' => 'SMTP主机不能为空',
            'port.required' => 'SMTP端口不能为空',
            'port.integer' => '端口必须是数字',
            'port.min' => '端口不能小于1',
            'port.max' => '端口不能大于65535',
            'encryption.in' => '加密方式必须是 tls 或 ssl',
            'username.required' => '用户名不能为空',
            'password.required' => '密码不能为空',
            'from_address.required' => '发件人邮箱不能为空',
            'from_address.email' => '发件人邮箱格式不正确',
            'from_name.required' => '发件人名称不能为空',
        ];
    }

    /**
     * 准备验证数据.
     */
    protected function prepareForValidation(): void
    {
        // 转换驼峰格式字段为下划线格式
        $this->merge([
            'from_address' => $this->fromAddress ?? $this->from_address,
            'from_name' => $this->fromName ?? $this->from_name,
        ]);

        // 确保端口是整数
        if ($this->has('port')) {
            $this->merge([
                'port' => (int) $this->port,
            ]);
        }

        // 确保状态是布尔值
        if ($this->has('status')) {
            $this->merge([
                'status' => filter_var($this->status, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
