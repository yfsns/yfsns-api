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

class BatchDestroyUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => [
                'required',
                'array',
                'min:1', // 最少删除一个用户
                'max:100' // 最多一次删除100个用户
            ],
            'ids.*' => [
                'integer',
                'exists:users,id', // 确保用户存在
                'distinct' // 防止重复ID
            ],
        ];
    }

    /**
     * 获取验证错误的自定义属性
     */
    public function attributes(): array
    {
        return [
            'ids' => '用户ID列表',
            'ids.*' => '用户ID',
        ];
    }

    /**
     * 获取验证错误的自定义消息
     */
    public function messages(): array
    {
        return [
            'ids.required' => '请选择要删除的用户',
            'ids.array' => '用户ID列表格式不正确',
            'ids.min' => '最少要选择1个用户',
            'ids.max' => '一次最多只能删除100个用户',
            'ids.*.integer' => '用户ID必须是整数',
            'ids.*.exists' => '用户不存在',
            'ids.*.distinct' => '用户ID不能重复',
        ];
    }

    /**
     * 获取每个参数的详细说明（用于接口文档）
     */
    public function bodyParameters(): array
    {
        return [
            'ids' => [
                'description' => '要批量删除的用户ID数组，最少1个，最多100个，ID不能重复。',
                'example' => [1, 2, 3],
            ],
        ];
    }}
