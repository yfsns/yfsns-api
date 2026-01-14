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

class GetCommentRepliesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100'
            ],
            'cursor' => [
                'sometimes',
                'string'
            ],
        ];
    }

    /**
     * 获取验证错误的自定义属性
     */
    public function attributes(): array
    {
        return [
            'limit' => '每页数量',
            'cursor' => '游标位置',
        ];
    }

    /**
     * 获取验证错误的自定义消息
     */
    public function messages(): array
    {
        return [
            'limit.integer' => '每页数量必须是整数',
            'limit.min' => '每页数量不能小于1',
            'limit.max' => '每页数量不能超过100',
            'cursor.string' => '游标参数格式不正确',
            'cursor.min' => '游标位置不能小于1',
        ];
    }

    /**
     * 获取每个参数的详细说明（用于接口文档）
     */
    public function bodyParameters(): array
    {
        return [
            'limit' => [
                'description' => '每页显示的回复数量（1-100），默认10',
                'example' => 20,
            ],
            'cursor' => [
                'description' => '游标位置：上一次加载的最后一条回复ID',
                'example' => 456,
            ],
        ];
    }

    /**
     * 准备验证数据
     */
    protected function prepareForValidation(): void
    {
        // 设置默认值
        if (!$this->has('limit')) {
            $this->merge(['limit' => 10]);
        }
    }}
