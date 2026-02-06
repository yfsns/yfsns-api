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

namespace App\Modules\Post\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetArticlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filter' => 'nullable|string|in:all,media,liked,user,my,following,topic',
            'user_id' => 'nullable|integer',
            'topic_id' => 'nullable|integer',
            'topicName' => 'nullable|string|max:100',
            'cursor' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:50',
            'page' => 'nullable|integer|min:1',
        ];
    }

    /**
     * 准备验证数据
     */
    protected function prepareForValidation(): void
    {
        // 自动设置 type 为 article（文章）
        $this->merge([
            'type' => 'article',
        ]);
    }

    public function messages(): array
    {
        return [
            'filter.in' => '筛选类型无效',
            'user_id.integer' => '用户ID必须是整数',
            'topic_id.integer' => '话题ID必须是整数',
            'topicName.max' => '话题名称不能超过100个字符',
            'cursor.string' => '游标必须是字符串',
            'limit.integer' => '每页数量必须是整数',
            'limit.min' => '每页数量不能小于1',
            'limit.max' => '每页数量不能超过50',
            'page.integer' => '页码必须是整数',
            'page.min' => '页码不能小于1',
        ];
    }
}