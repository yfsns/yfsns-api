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

class PostRequest extends FormRequest
{
    protected ?string $action = null;

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // 如果action是repost，直接返回repost规则
        if ($this->action === 'repost') {
            return [
                'type' => 'required|string|in:post',  // 转发必须是post类型
                'content' => 'nullable|string|max:1000',
            ];
        }

        $ruleSets = [
            'store' => [
                'content' => 'required|string|max:2000',
                'type' => 'nullable|string|in:post,article,question,thread,image,video',
                'visibility' => 'nullable|integer|in:1,2,3,4',
                'file_ids' => 'nullable|array|max:20',
                'file_ids.*' => 'integer|exists:files,id|distinct',
                'cover_id' => 'nullable|integer|in_array:file_ids.*',
                'location' => 'nullable|array',
                'mentions' => 'nullable|array|max:20',
                'mentions.*' => 'integer|exists:users,id|distinct',
                'topics' => 'nullable|array|max:10',
                'topics.*' => 'integer|exists:topics,id|distinct',
            ],
            'update' => [
                'content' => 'nullable|string|max:2000',
                'type' => 'nullable|string|in:post,article,question,thread,image,video',
                'visibility' => 'nullable|integer|in:1,2,3,4',
                'status' => 'nullable|integer|in:0,1,2,3',
                'file_ids' => 'nullable|array|max:20',
                'file_ids.*' => 'integer|exists:files,id|distinct',
                'cover_id' => 'nullable|integer|in_array:file_ids.*',
                'location' => 'nullable|array',
                'mentions' => 'nullable|array|max:20',
                'mentions.*' => 'integer|exists:users,id|distinct',
                'topics' => 'nullable|array|max:10',
                'topics.*' => 'integer|exists:topics,id|distinct',
            ],
            'get_detail' => [
                'type' => 'required|string|in:post,article,question,thread,image,video',
            ],
            'get_list' => [
                'type' => 'required|string|in:post,article,question,thread,image,video',
                'filter' => 'nullable|string|in:all,media,liked,user,my,following,topic',
                'user_id' => 'nullable|integer',
                'topic_id' => 'nullable|integer',
                'cursor' => 'nullable|string',
                'limit' => 'nullable|integer|min:1|max:50|required_with:cursor',
                'page' => 'nullable|integer|min:1',
            ],
        ];

        return $ruleSets[$this->action] ?? $ruleSets['get_list'];
    }


    /**
     * 准备验证数据
     */
    protected function prepareForValidation(): void
    {
        // 为转发请求自动设置type字段
        if ($this->action === 'repost' && !$this->has('type')) {
            $this->merge(['type' => 'post']);
        }

        // 转换驼峰格式字段为下划线格式
        $this->merge([
            'user_id' => $this->userId ?? $this->user_id,
            'topic_id' => $this->topicId ?? $this->topic_id,
            'file_ids' => $this->fileIds ?? $this->file_ids,
            'cover_id' => $this->coverId ?? $this->cover_id,
        ]);
    }
}