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

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:2000', // 动态内容必填
            'type' => 'nullable|string|in:post', // 动态只支持post类型
            'visibility' => 'nullable|integer|in:1,2,3,4',
            'repost_id' => 'nullable|integer|exists:posts,id',
            'file_ids' => 'nullable|array|max:20', // 动态可以包含文件
            'file_ids.*' => 'integer|exists:files,id|distinct',
            'location' => 'nullable|array',
            'mentions' => 'nullable|array|max:20',
            'mentions.*' => 'integer|exists:users,id|distinct',
            'topics' => 'nullable|array|max:10',
            'topics.*' => 'integer|exists:topics,id|distinct',
        ];
    }

    /**
     * 准备验证数据
     */
    protected function prepareForValidation(): void
    {
        // 自动设置 type 为 post（动态）
        $this->merge([
            'type' => 'post',
            'file_ids' => $this->fileIds ?? $this->file_ids,
        ]);
    }

    /**
     * 自定义验证规则
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 动态类型不需要特殊的额外验证
            // 可以在这里添加动态特有的验证逻辑
        });
    }
}
