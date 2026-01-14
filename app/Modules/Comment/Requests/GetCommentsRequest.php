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

namespace App\Modules\Comment\Requests;

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
            'target_id' => [
                'required',
                'integer',
                'min:1'
            ],
            'target_type' => [
                'required',
                'string',
                'in:post,article,comment'
            ],
            // 分页参数（可选，默认使用传统分页）
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
            // 排序方式（可选）
            'sort' => [
                'sometimes',
                'string',
                'in:latest,hot'
            ],
        ];
    }

    /**
     * 获取验证错误的自定义属性
     */
    public function attributes(): array
    {
        return [
            'target_id' => '目标ID',
            'target_type' => '目标类型',
            'page' => '页码',
            'per_page' => '每页数量',
            'sort' => '排序方式',
        ];
    }

    /**
     * 获取验证错误的自定义消息
     */
    public function messages(): array
    {
        return [
            'target_id.required' => '请选择要查看评论的目标',
            'target_id.integer' => '目标ID必须是整数',
            'target_id.min' => '目标ID不能小于1',
            'target_type.required' => '请选择目标类型',
            'target_type.in' => '目标类型只能是：文章、帖子或评论',
            'page.integer' => '页码必须是整数',
            'page.min' => '页码不能小于1',
            'per_page.integer' => '每页数量必须是整数',
            'per_page.min' => '每页数量不能小于1',
            'per_page.max' => '每页数量不能超过100',
            'sort.in' => '排序方式只能是：latest（最新）或hot（最热）',
        ];
    }

    /**
     * 获取每个参数的详细说明（用于接口文档）
     */
    public function bodyParameters(): array
    {
        return [
            'target_id' => [
                'description' => '要查看评论的目标ID（文章ID或帖子ID）',
                'example' => 123,
            ],
            'target_type' => [
                'description' => '目标类型：post（帖子）或article（文章）',
                'example' => 'post',
            ],
            'page' => [
                'description' => '页码，从1开始，默认1',
                'example' => 1,
            ],
            'per_page' => [
                'description' => '每页显示的评论数量（1-100），默认10',
                'example' => 10,
            ],
            'sort' => [
                'description' => '排序方式：latest（按时间倒序）或hot（按热度排序），默认latest',
                'example' => 'latest',
            ],
        ];
    }

    /**
     * 准备验证数据.
     * 转换前端驼峰格式字段为后端下划线格式.
     */
    protected function prepareForValidation(): void
    {
        // 转换驼峰格式字段为下划线格式（只在字段存在时转换）
        $mergeData = [];
        if ($this->has('targetId') || $this->has('target_id')) {
            $mergeData['target_id'] = $this->targetId ?? $this->target_id;
        }
        if ($this->has('targetType') || $this->has('target_type')) {
            $mergeData['target_type'] = $this->targetType ?? $this->target_type;
        }
        if ($this->has('perPage') || $this->has('per_page')) {
            $mergeData['per_page'] = $this->perPage ?? $this->per_page;
        }

        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }

        // 设置默认值 - 简化分页，默认使用传统分页
        if (!$this->has('page')) {
            $this->merge(['page' => 1]);
        }
        if (!$this->has('per_page')) {
            $this->merge(['per_page' => 10]);
        }
        if (!$this->has('sort')) {
            $this->merge(['sort' => 'latest']);
        }
    }
}
