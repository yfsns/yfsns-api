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

class StoreStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:10000',
            'visibility' => 'nullable|integer|in:1,2,3,4',
            'file_ids' => 'required|array|min:1|max:9',
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
        // 转换驼峰格式字段为下划线格式
        $this->merge([
            'file_ids' => $this->fileIds ?? $this->file_ids,
            // mentions 和 topics 字段名已经一致，无需转换
        ]);
    }

    public function messages(): array
    {
        return [
            'title.max' => '故事标题不能超过255个字符',
            'content.max' => '故事内容不能超过10000个字符',
            'visibility.in' => '可见性设置无效',
            'file_ids.required' => '至少需要上传一张图片',
            'file_ids.array' => '图片ID必须是数组',
            'file_ids.min' => '至少需要上传一张图片',
            'file_ids.max' => '最多只能上传9张图片',
            'file_ids.*.exists' => '选择的图片不存在',
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
            // 验证图片文件确实是图片类型
            $fileIds = $this->input('file_ids', []);
            if (!empty($fileIds)) {
                $imageFiles = \App\Modules\File\Models\File::whereIn('id', $fileIds)
                    ->where('type', \App\Modules\File\Models\File::TYPE_IMAGE)
                    ->count();

                if ($imageFiles !== count($fileIds)) {
                    $validator->errors()->add('file_ids', '只能上传图片文件');
                }
            }

            // 如果有内容，检查内容长度
            $content = $this->input('content');
            if ($content && strlen(strip_tags($content)) < 2) {
                $validator->errors()->add('content', '故事内容不能少于2个字符');
            }
        });
    }
}