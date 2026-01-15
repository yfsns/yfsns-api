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

namespace App\Modules\Comment\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AuditCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'integer',
                'in:1,2' // 1:通过审核, 2:拒绝审核
            ],
            'reason' => [
                'sometimes',
                'string',
                'max:500',
                'nullable',
                'required_if:status,2' // 拒绝时必须填写原因
            ],
        ];
    }

    /**
     * 获取验证错误的自定义属性
     */
    public function attributes(): array
    {
        return [
            'status' => '审核结果',
            'reason' => '审核原因',
        ];
    }

    /**
     * 获取验证错误的自定义消息
     */
    public function messages(): array
    {
        return [
            'status.required' => '请选择审核结果',
            'status.integer' => '审核结果必须是整数',
            'status.in' => '审核结果只能是：1（通过）或2（拒绝）',
            'reason.string' => '审核原因必须是字符串',
            'reason.max' => '审核原因长度不能超过500个字符',
            'reason.required_if' => '拒绝审核时必须填写原因',
        ];
    }

    /**
     * 获取每个参数的详细说明（用于接口文档）
     */
    public function bodyParameters(): array
    {
        return [
            'status' => [
                'description' => '审核结果：1（通过审核）、2（拒绝审核）',
                'example' => 1,
            ],
            'reason' => [
                'description' => '审核原因说明，拒绝审核时必填，最多500字符',
                'example' => '内容包含不当言论',
            ],
        ];
    }}
