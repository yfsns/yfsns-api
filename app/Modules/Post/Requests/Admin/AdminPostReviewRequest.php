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

namespace App\Modules\Post\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 管理员审核动态请求类
 */
class AdminPostReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|integer|in:0,1,2,3',
            'remark' => 'nullable|string|max:500',
        ];
    }

    /**
     * 获取验证错误的自定义属性
     */
    public function attributes(): array
    {
        return [
            'status' => '审核状态',
            'remark' => '审核备注',
        ];
    }

    /**
     * 获取验证错误的自定义消息
     */
    public function messages(): array
    {
        return [
            // 状态相关
            'status.required' => '审核状态不能为空',
            'status.in' => '审核状态只能是：0（草稿）、1（已发布）、2（审核中）或3（已拒绝）',
            'status.integer' => '审核状态必须是整数',

            // 审核相关
            'remark.max' => '审核备注长度不能超过500个字符',
        ];
    }

    /**
     * 获取每个参数的详细说明（用于接口文档）
     */
    public function bodyParameters(): array
    {
        return [
            // 审核参数
            'status' => [
                'description' => '审核状态：0（草稿）、1（已发布）、2（审核中）、3（已拒绝）',
                'example' => 1,
            ],
            'remark' => [
                'description' => '审核备注，最多500字符',
                'example' => '内容审核通过',
            ],
        ];
    }
}
