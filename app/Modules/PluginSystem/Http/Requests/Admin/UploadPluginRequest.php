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

namespace App\Modules\PluginSystem\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadPluginRequest extends FormRequest
{
    /**
     * 判断用户是否有权限进行此请求
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
            'file' => 'required|file|mimes:zip|max:10240', // 最大 10MB
            'plugin_name' => 'required|string|max:100|regex:/^[a-zA-Z][a-zA-Z0-9_]*$/',
            'auto_install' => 'nullable|boolean',
        ];
    }

    /**
     * 获取验证错误消息.
     */
    public function messages(): array
    {
        return [
            'file.required' => '文件不能为空',
            'file.file' => '必须是有效的文件',
            'file.mimes' => '文件必须是 zip 格式',
            'file.max' => '文件大小不能超过 10MB',
            'plugin_name.required' => '插件名称不能为空',
            'plugin_name.regex' => '插件名称只能包含字母、数字和下划线，且必须以字母开头',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => '文件',
            'plugin_name' => '插件名称',
            'auto_install' => '自动安装',
        ];
    }
}
