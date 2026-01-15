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

class GetCommentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => [
                'sometimes',
                'integer',
                'min:1'
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
                'required_with:page' // 如果指定页码，则每页数量必填
            ],
            'status' => [
                'sometimes',
                'integer',
                'in:0,1,2' // 0:待审核, 1:已发布, 2:已拒绝
            ],
            'keyword' => [
                'sometimes',
                'string',
                'max:200',
                'nullable'
            ],
            'target_type' => [
                'nullable',
                'string',
                'in:post,article,comment'
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id'
            ],
            'sort_field' => [
                'nullable',
                'string',
                'in:id,created_at,updated_at,status,likes_count,replies_count'
            ],
            'sort_order' => [
                'nullable',
                'string',
                'in:asc,desc'
            ],
        ];
    }

    /**
     * 获取验证错误的自定义属性
     */
    public function attributes(): array
    {
        return [
            'page' => '页码',
            'per_page' => '每页数量',
            'status' => '审核状态',
            'keyword' => '搜索关键词',
            'target_type' => '目标类型',
            'user_id' => '用户ID',
            'sort_field' => '排序字段',
            'sort_order' => '排序顺序',
        ];
    }

    /**
     * 获取验证错误的自定义消息
     */
    public function messages(): array
    {
        return [
            'page.integer' => '页码必须是整数',
            'page.min' => '页码不能小于1',
            'per_page.integer' => '每页数量必须是整数',
            'per_page.min' => '每页数量不能小于1',
            'per_page.max' => '每页数量不能超过100',
            'per_page.required_with' => '指定页码时必须提供每页数量',
            'status.in' => '审核状态只能是：0（待审核）、1（已发布）或2（已拒绝）',
            'keyword.max' => '搜索关键词长度不能超过200个字符',
            'target_type.in' => '目标类型只能是：post（帖子）、article（文章）或comment（评论）',
            'user_id.exists' => '指定的用户不存在',
            'sort_field.in' => '排序字段不正确',
            'sort_order.in' => '排序顺序只能是：asc（升序）或desc（降序）',
        ];
    }

    /**
     * 获取每个参数的详细说明（用于接口文档）
     */
    public function bodyParameters(): array
    {
        return [
            'page' => [
                'description' => '页码，从1开始',
                'example' => 1,
            ],
            'per_page' => [
                'description' => '每页显示的评论数量（1-100）',
                'example' => 20,
            ],
            'status' => [
                'description' => '审核状态：0（待审核）、1（已发布）、2（已拒绝）',
                'example' => 1,
            ],
            'keyword' => [
                'description' => '搜索关键词，用于搜索评论内容',
                'example' => '测试评论',
            ],
            'target_type' => [
                'description' => '按目标类型筛选：post（帖子）、article（文章）或comment（评论）',
                'example' => 'post',
            ],
            'user_id' => [
                'description' => '按用户ID筛选评论',
                'example' => 123,
            ],
            'sort_field' => [
                'description' => '排序字段：id、created_at、updated_at、status、likes_count、replies_count',
                'example' => 'created_at',
            ],
            'sort_order' => [
                'description' => '排序顺序：asc（升序）或desc（降序）',
                'example' => 'desc',
            ],
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
            'per_page' => $this->input('perPage') ?? $this->input('per_page'),
            'user_id' => $this->input('userId') ?? $this->input('user_id'),
            'target_type' => $this->input('targetType') ?? $this->input('target_type'),
            'sort_field' => $this->input('sortField') ?? $this->input('sort_field'),
            'sort_order' => $this->input('sortOrder') ?? $this->input('sort_order'),
        ]);

        // 设置默认值
        if (!$this->has('per_page')) {
            $this->merge(['per_page' => 20]);
        }
        if (!$this->has('sort_field')) {
            $this->merge(['sort_field' => 'created_at']);
        }
        if (!$this->has('sort_order')) {
            $this->merge(['sort_order' => 'desc']);
        }
    }
}
