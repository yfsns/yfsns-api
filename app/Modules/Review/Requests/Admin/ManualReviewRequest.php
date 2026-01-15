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

namespace App\Modules\Review\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ManualReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // 如果路径中有ID，content_id 可以为空（会使用路径参数），但 content_type 必须提供
        $hasIdInPath = request()->route('id');

        return [
            'content_type' => 'required|string|in:article,post,thread,comment',
            'content_id' => $hasIdInPath ? 'nullable|integer' : 'required|integer',
            'status' => 'required|string|in:published,rejected,pending,approved',
            'remark' => 'nullable|string|max:500',
            'extra_data' => 'nullable|array',  // 扩展数据，各模块自定义参数
            'extra_data.reason' => 'nullable|string|max:100',  // 审核原因分类
            'extra_data.tags' => 'nullable|array',             // 违规标签
            'extra_data.score' => 'nullable|numeric|min:0|max:100', // 审核分数
            'extra_data.module_params' => 'nullable|array',    // 模块特定参数
        ];
    }

    public function messages(): array
    {
        return [
            'content_type.required' => '内容类型（content_type）不能为空，请指定 article、post、thread 或 comment',
            'content_type.in' => '内容类型（content_type）必须是 article、post、thread 或 comment 之一',
            'content_id.required' => '内容ID（content_id）不能为空',
            'content_id.integer' => '内容ID（content_id）必须是整数',
            'status.required' => '审核状态（status）不能为空',
            'status.in' => '审核状态（status）必须是 published、rejected、pending 或 approved 之一',
            'remark.max' => '审核备注（remark）不能超过 500 个字符',
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
            'content_type' => $this->contentType ?? $this->content_type,
            'content_id' => $this->contentId ?? $this->content_id,
        ]);
    }
}
