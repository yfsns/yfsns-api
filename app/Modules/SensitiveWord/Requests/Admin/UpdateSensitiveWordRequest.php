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

namespace App\Modules\SensitiveWord\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSensitiveWordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'word' => 'sometimes|string|max:100|unique:sensitive_words,word,' . $id,
            'category' => 'sometimes|in:political,pornographic,violence,advertising,illegal,other',
            'level' => 'sometimes|in:low,medium,high',
            'action' => 'sometimes|in:replace,reject,review',
            'replacement' => 'nullable|string|max:100',
            'is_regex' => 'nullable|boolean',
            'status' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
        ];
    }

    /**
     * 准备验证数据.
     * 转换前端驼峰格式字段为后端下划线格式.
     */
    protected function prepareForValidation(): void
    {
        // 转换驼峰格式字段为下划线格式
        $this->merge([
            'is_regex' => $this->isRegex ?? $this->is_regex,
        ]);
    }
}
