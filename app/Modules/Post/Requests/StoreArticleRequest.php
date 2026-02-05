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

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:50000',
            'visibility' => 'nullable|integer|in:1,2,3,4',
            'file_ids' => 'nullable|array|max:20',
            'file_ids.*' => 'integer|exists:files,id|distinct',
            'location' => 'nullable|array',
            'mentions' => 'nullable|array|max:20',
            'mentions.*' => 'integer|exists:users,id|distinct',
            'topics' => 'nullable|array|max:10',
            'topics.*' => 'integer|exists:topics,id|distinct',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '文章标题不能为空',
            'title.max' => '文章标题不能超过255个字符',
            'content.required' => '文章内容不能为空',
            'content.max' => '文章内容不能超过50000个字符',
            'visibility.in' => '可见性设置无效',
            'file_ids.array' => '文件ID必须是数组',
            'file_ids.max' => '最多只能上传20个文件',
            'file_ids.*.exists' => '选择的文件不存在',
            'mentions.array' => '@用户必须是数组',
            'mentions.max' => '最多只能@20个用户',
            'mentions.*.exists' => '@的用户不存在',
            'topics.array' => '话题必须是数组',
            'topics.max' => '最多只能关联10个话题',
            'topics.*.exists' => '选择的话题不存在',
        ];
    }

    /**
     * 自定义验证逻辑
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // 文章特定的验证逻辑可以在这里添加
            $content = $this->input('content');
            if ($content && strlen(strip_tags($content)) < 10) {
                $validator->errors()->add('content', '文章内容不能少于10个字符');
            }
        });
    }
}