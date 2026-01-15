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

class BatchAuditCommentsRequest extends FormRequest
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
                'min:1',        // 最少审核一个评论
                'max:50'        // 最多一次审核50个评论
            ],
            'ids.*' => [
                'integer',
                'exists:comments,id',  // 确保评论存在
                'distinct'             // 防止重复ID
            ],
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
            'ids' => '评论ID列表',
            'ids.*' => '评论ID',
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
            'ids.required' => '请选择要审核的评论',
            'ids.array' => '评论ID列表格式不正确',
            'ids.min' => '最少要选择1个评论',
            'ids.max' => '一次最多只能审核50个评论',
            'ids.*.integer' => '评论ID必须是整数',
            'ids.*.exists' => '评论不存在',
            'ids.*.distinct' => '评论ID不能重复',
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
            'ids' => [
                'description' => '要批量审核的评论ID数组，最少1个，最多50个，ID不能重复',
                'example' => [1, 2, 3],
            ],
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
