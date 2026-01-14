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

class MarkMentionsAsReadRequest extends FormRequest
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
            'mention_ids' => 'required|array|min:1|max:50',
            'mention_ids.*' => 'required|integer|min:1',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'mention_ids.required' => '@记录ID列表不能为空',
            'mention_ids.array' => '@记录ID列表必须是数组',
            'mention_ids.min' => '@记录ID列表不能为空',
            'mention_ids.max' => '@记录ID列表最多不能超过50个',
            'mention_ids.*.required' => '@记录ID不能为空',
            'mention_ids.*.integer' => '@记录ID必须是整数',
            'mention_ids.*.min' => '@记录ID必须大于0',
        ];
    }
}
