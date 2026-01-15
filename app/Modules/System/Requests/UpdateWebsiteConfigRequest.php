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

class UpdateWebsiteConfigRequest extends FormRequest
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
        $rules = [
            'site_name' => 'nullable|string|max:100',
            'site_url' => 'nullable|string|max:255',
            'site_description' => 'nullable|string|max:500',
            'site_keywords' => 'nullable|string|max:200',
            'site_title' => 'nullable|string|max:200',
            'site_logo' => 'nullable|string|max:255',
            'site_header_logo' => 'nullable|string|max:255',
            'site_favicon' => 'nullable|string|max:255',
            'site_status' => 'nullable|boolean',
            'icp_number' => 'nullable|string|max:50',
            'police_record' => 'nullable|string|max:50',
            'statistics_code' => 'nullable|string',
        ];

        // 如果提供了 site_url 且不为空，则验证 URL 格式
        if ($this->has('site_url') && ! empty(trim($this->input('site_url')))) {
            $rules['site_url'] = 'nullable|url|max:255';
        }

        return $rules;
    }

    /**
     * 准备验证数据.
     * 将驼峰格式转换为下划线格式供数据库使用.
     */
    protected function prepareForValidation(): void
    {
        // 将驼峰格式转换为下划线格式
        $convertedData = [];

        // 字段映射：前端驼峰格式 -> 数据库下划线格式
        $fieldMapping = [
            'siteName' => 'site_name',
            'siteUrl' => 'site_url',
            'siteDescription' => 'site_description',
            'siteKeywords' => 'site_keywords',
            'siteTitle' => 'site_title',
            'siteLogo' => 'site_logo',
            'siteHeaderLogo' => 'site_header_logo',
            'siteFavicon' => 'site_favicon',
            'siteStatus' => 'site_status',
            'icpNumber' => 'icp_number',
            'policeRecord' => 'police_record',
            'statisticsCode' => 'statistics_code',
        ];

        // 转换字段名
        foreach ($fieldMapping as $camelCase => $snakeCase) {
            if ($this->has($camelCase)) {
                $convertedData[$snakeCase] = $this->input($camelCase);
            } elseif ($this->has($snakeCase)) {
                $convertedData[$snakeCase] = $this->input($snakeCase);
            }
        }

        // 合并转换后的数据
        $this->merge($convertedData);
    }
}
