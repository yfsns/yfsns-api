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

namespace App\Modules\Category\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('categories', 'name')->ignore($categoryId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($categoryId) {
                    // 防止形成循环引用
                    if ($value == $categoryId) {
                        $fail('不能将分类设为自己的父分类');
                    }
                },
            ],
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_system' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => '分类名称不能超过50个字符',
            'name.unique' => '分类名称已存在',
            'slug.regex' => '分类别名只能包含小写字母、数字和连字符',
            'slug.unique' => '分类别名已存在',
            'color.regex' => '分类颜色格式不正确，应为6位十六进制颜色值',
            'parent_id.exists' => '父分类不存在',
        ];
    }
}