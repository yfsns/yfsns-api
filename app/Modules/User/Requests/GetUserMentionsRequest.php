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

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetUserMentionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content_type' => 'nullable|in:post,comment,message',
            'status' => 'nullable|in:unread,read',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'content_type.in' => '内容类型只能是：post, comment, message',
            'status.in' => '状态只能是：unread, read',
            'per_page.integer' => '每页数量必须是整数',
            'per_page.min' => '每页数量不能小于1',
            'per_page.max' => '每页数量不能超过100',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // 设置默认值
        if (!$this->has('per_page')) {
            $this->merge(['per_page' => 20]);
        }
    }
}
