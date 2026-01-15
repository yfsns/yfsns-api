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
use App\Modules\User\Models\UserRole;

use function array_key_exists;

use Illuminate\Validation\Rule;

use function is_string;

class UserRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => 'required|string|max:50',
            'key' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('user_roles', 'key')->ignore($roleId),
            ],
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'type' => 'nullable|integer|in:0,1,2',
            'status' => 'nullable|integer|in:0,1',
            'sort' => 'nullable|integer|min:0',
            'isPaidRole' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => '角色名称不能为空',
            'name.max' => '角色名称不能超过50个字符',
            'key.required' => '角色标识不能为空',
            'key.max' => '角色标识不能超过50个字符',
            'key.regex' => '角色标识只能包含小写字母、数字和下划线，且必须以字母开头',
            'key.unique' => '角色标识已存在',
            'description.max' => '角色描述不能超过255个字符',
            'permissions.array' => '权限列表格式错误',
            'type.in' => '角色类型值无效',
            'status.in' => '状态值无效',
            'sort.integer' => '排序值必须是整数',
            'sort.min' => '排序值不能小于0',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => '角色名称',
            'key' => '角色标识',
            'description' => '角色描述',
            'permissions' => '权限列表',
            'type' => '角色类型',
            'status' => '状态',
            'sort' => '排序',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if ($this->has('status')) {
            $status = $this->input('status');
            if (is_string($status)) {
                $map = [
                    'active' => 1,
                    'enabled' => 1,
                    'normal' => 1,
                    'inactive' => 0,
                    'disabled' => 0,
                    'banned' => 0,
                ];
                if (array_key_exists($status, $map)) {
                    $normalized['status'] = $map[$status];
                }
            }
        }

        if ($this->has('isPaidRole')) {
            $normalized['type'] = $this->boolean('isPaidRole')
                ? UserRole::TYPE_PREMIUM
                : UserRole::TYPE_NORMAL;
        }

        if (! empty($normalized)) {
            $this->merge($normalized);
        }

        // 转换驼峰格式字段为下划线格式
        $this->merge([
            'key' => $this->roleKey ?? $this->key,
        ]);
    }
}
