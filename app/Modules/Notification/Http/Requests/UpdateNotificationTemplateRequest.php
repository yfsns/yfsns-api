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

namespace App\Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:100',
            'type' => 'sometimes|string|in:system,email,sms',
            'category' => 'sometimes|string|in:general,user,order,security,social',
            'channels' => 'sometimes|array',
            'channels.*' => 'string|in:database,mail,sms',
            'content' => 'sometimes|array',
            'content.*' => 'string',
            'variables' => 'sometimes|array',
            'variables.*' => 'string',
            'sms_template_id' => 'nullable|string|max:50',
            'status' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|in:1,2,3',
            'remark' => 'nullable|string|max:500',
        ];
    }

    /**
     * 准备验证数据.
     * 转换前端驼峰格式字段为后端下划线格式.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        // 转换驼峰格式字段为下划线格式
        if ($this->has('smsTemplateId')) {
            $data['sms_template_id'] = $this->smsTemplateId;
        }

        if (! empty($data)) {
            $this->merge($data);
        }
    }
}

