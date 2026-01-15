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

namespace App\Modules\User\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $permissionId = $this->route('permission')?->id;

        return [
            'name' => 'required|string|max:50',
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_.]*$/',
                Rule::unique('user_permissions', 'slug')->ignore($permissionId),
            ],
            'description' => 'nullable|string|max:255',
            'module' => 'required|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '权限名称不能为空',
            'name.max' => '权限名称不能超过50个字符',
            'slug.required' => '权限标识不能为空',
            'slug.max' => '权限标识不能超过100个字符',
            'slug.regex' => '权限标识需以字母开头，仅包含小写字母、数字、下划线或点',
            'slug.unique' => '权限标识已存在',
            'description.max' => '权限描述不能超过255个字符',
            'module.required' => '所属模块不能为空',
            'module.max' => '所属模块不能超过50个字符',
        ];
    }}
