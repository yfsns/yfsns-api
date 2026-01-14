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
 * 获取操作日志列表请求
 */
class GetLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'user_id' => 'nullable|integer|exists:users,id',
            'module' => 'nullable|string|max:50',
            'action' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'ip' => 'nullable|ip',
        ];
    }

    /**
     * 获取验证后的数据，自动过滤空值
     *
     * @param null|mixed $key
     * @param null|mixed $default
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // 过滤空值（null, '', [], false 保留，只过滤 null 和空字符串）
        return array_filter($validated, function ($value) {
            return $value !== null && $value !== '';
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
            'per_page' => $this->perPage ?? $this->per_page,
            'page' => $this->currentPage ?? $this->page,
            'user_id' => $this->userId ?? $this->user_id,
            'start_date' => $this->startDate ?? $this->start_date,
            'end_date' => $this->endDate ?? $this->end_date,
        ]);
    }
}
