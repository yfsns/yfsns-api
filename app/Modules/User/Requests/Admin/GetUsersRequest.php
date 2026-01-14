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

class GetUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => [
                'sometimes',
                'string',
                'max:50',
                'nullable' // 允许空字符串
            ],
            'status' => [
                'sometimes',
                'integer',
                'in:0,1' // 0=禁用，1=启用
            ],
            'roleId' => [
                'sometimes',
                'integer',
                'exists:user_roles,id'
            ],
            'sortField' => [
                'sometimes',
                'string',
                'in:id,username,nickname,email,created_at,updated_at,last_login_at'
            ],
            'sortOrder' => [
                'sometimes',
                'string',
                'in:asc,desc'
            ],
            'page' => [
                'sometimes',
                'integer',
                'min:1'
            ],
            'perPage' => [
                'sometimes',
                'integer',
                'in:10,20,50,100'
            ],
        ];
    }

    /**
     * 获取验证错误的自定义属性
     */
    public function attributes(): array
    {
        return [
            'keyword' => '搜索关键词',
            'status' => '用户状态',
            'roleId' => '用户角色',
            'sortField' => '排序字段',
            'sortOrder' => '排序顺序',
            'page' => '页码',
            'perPage' => '每页数量',
        ];
    }

    /**
     * 获取验证错误的自定义消息
     */
    public function messages(): array
    {
        return [
            'keyword.max' => '搜索关键词长度不能超过50个字符',
            'status.in' => '用户状态只能是：0（禁用）或1（启用）',
            'roleId.exists' => '选择的角色不存在',
            'sortField.in' => '排序字段不正确',
            'sortOrder.in' => '排序顺序只能是：asc（升序）或desc（降序）',
            'page.min' => '页码不能小于1',
            'perPage.in' => '每页数量只能是：10、20、50或100',
        ];
    }

    /**
     * 准备验证数据.
     * 保持前端驼峰格式参数不变.
     */
    protected function prepareForValidation(): void
    {
        $mappings = [
            'perPage' => ['type' => 'int'],
            'sortField' => ['type' => 'string'],
            'sortOrder' => ['type' => 'string'],
            'roleId' => ['type' => 'int'],
        ];

        $data = [];
        foreach ($mappings as $param => $config) {
            $value = $this->query($param) ?: $this->input($param);
            if ($value !== null) {
                $data[$param] = $config['type'] === 'int' ? (int) $value : $value;
            }
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }
}
