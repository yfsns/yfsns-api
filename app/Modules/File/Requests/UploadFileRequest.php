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

namespace App\Modules\File\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $limits = config('upload.limits');

        return [
            'file' => [
                'sometimes',
                'file',
                'mimes:' . $limits['allowed_mimes'],
                'max:' . $limits['max_file_size']
            ],
            'files' => [
                'sometimes',
                'array',
                'min:1',
                'max:' . $limits['max_batch_files']
            ],
            'files.*' => [
                'file',
                'mimes:' . $limits['allowed_mimes'],
                'max:' . $limits['max_file_size']
            ],
            'type' => 'required|string',
            'module' => 'nullable|string',
            'module_id' => 'nullable',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $hasFile = $this->hasFile('file');
            $hasFiles = $this->has('files') && is_array($this->input('files'));

            if (!$hasFile && !$hasFiles) {
                $validator->errors()->add('file', '请上传文件');
                return;
            }

            if ($hasFile && $hasFiles) {
                $validator->errors()->add('file', '不能同时上传单个文件和多个文件');
                return;
            }
        });
    }

    /**
     * 准备验证数据.
     * 转换前端驼峰格式字段为后端下划线格式.
     */
    protected function prepareForValidation(): void
    {
        // 转换驼峰格式字段为下划线格式
        $this->merge([
            'module_id' => $this->moduleId ?? $this->module_id,
        ]);
    }
}
