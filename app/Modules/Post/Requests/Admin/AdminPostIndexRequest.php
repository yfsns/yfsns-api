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
 * 管理员动态列表查询请求类
 */
class AdminPostIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|integer|in:0,1,2,3',
            'type' => 'nullable|string|in:post,article,question,thread,image,video',
            'user_id' => 'nullable|integer|exists:users,id',
            'keyword' => 'nullable|string|max:200',
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'sort_field' => 'nullable|string|in:id,title,content,created_at,updated_at,views_count,likes_count,comments_count',
            'sort_order' => 'nullable|string|in:asc,desc',
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
            'type' => '内容类型',
            'user_id' => '用户ID',
            'keyword' => '搜索关键词',
            'start_date' => '开始日期',
            'end_date' => '结束日期',
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
            // 分页相关
            'page.integer' => '页码必须是整数',
            'page.min' => '页码不能小于1',
            'per_page.integer' => '每页数量必须是整数',
            'per_page.min' => '每页数量不能小于1',
            'per_page.max' => '每页数量不能超过100',

            // 状态相关
            'status.in' => '审核状态只能是：0（草稿）、1（已发布）、2（审核中）或3（已拒绝）',
            'status.integer' => '审核状态必须是整数',

            // 类型相关
            'type.in' => '内容类型只能是：动态、文章、问题、话题、图片或视频',

            // 用户相关
            'user_id.exists' => '指定的用户不存在',

            // 搜索相关
            'keyword.max' => '搜索关键词长度不能超过200个字符',

            // 日期相关
            'start_date.date' => '开始日期格式不正确',
            'start_date.before_or_equal' => '开始日期不能晚于结束日期',
            'end_date.date' => '结束日期格式不正确',
            'end_date.after_or_equal' => '结束日期不能早于开始日期',

            // 排序相关
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
            // 分页参数
            'page' => [
                'description' => '页码，从1开始',
                'example' => 1,
            ],
            'per_page' => [
                'description' => '每页显示的数量（1-100）',
                'example' => 20,
            ],

            // 筛选参数
            'status' => [
                'description' => '审核状态：0（草稿）、1（已发布）、2（审核中）、3（已拒绝）',
                'example' => 1,
            ],
            'type' => [
                'description' => '内容类型：post（动态）、article（文章）、question（问题）、thread（话题）、image（图片）、video（视频）',
                'example' => 'post',
            ],
            'user_id' => [
                'description' => '按用户ID筛选',
                'example' => 123,
            ],
            'keyword' => [
                'description' => '搜索关键词，用于搜索标题和内容',
                'example' => '技术分享',
            ],
            'start_date' => [
                'description' => '发布时间开始日期（YYYY-MM-DD格式）',
                'example' => '2024-01-01',
            ],
            'end_date' => [
                'description' => '发布时间结束日期（YYYY-MM-DD格式）',
                'example' => '2024-12-31',
            ],

            // 排序参数
            'sort_field' => [
                'description' => '排序字段：id、title、content、created_at、updated_at、views_count、likes_count、comments_count',
                'example' => 'created_at',
            ],
            'sort_order' => [
                'description' => '排序顺序：asc（升序）或desc（降序）',
                'example' => 'desc',
            ],
        ];
    }

    /**
     * 准备验证数据
     */
    protected function prepareForValidation(): void
    {
        // 转换驼峰格式字段为下划线格式
        $this->merge([
            'per_page' => $this->perPage ?? $this->per_page,
            'user_id' => $this->userId ?? $this->user_id,
            'start_date' => $this->startDate ?? $this->start_date,
            'end_date' => $this->endDate ?? $this->end_date,
            'sort_field' => $this->sortField ?? $this->sort_field,
            'sort_order' => $this->sortOrder ?? $this->sort_order,
        ]);
    }
}
