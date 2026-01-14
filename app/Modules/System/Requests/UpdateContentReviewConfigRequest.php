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

namespace App\Modules\System\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 更新内容审核配置请求
 */
class UpdateContentReviewConfigRequest extends FormRequest
{
    /**
     * 确定用户是否有权限进行此请求
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 获取验证规则.
     */
    public function rules(): array
    {
        return [
            'settings' => 'required|array',
            'settings.*' => 'boolean',
        ];
    }

    /**
     * 获取验证错误消息.
     */
    public function messages(): array
    {
        return [
            'settings.required' => '配置项不能为空',
            'settings.array' => '配置项必须是数组',
            'settings.*.boolean' => '配置项的值必须是布尔类型',
        ];
    }

    /**
     * 需要转换的驼峰字段映射.
     */}
