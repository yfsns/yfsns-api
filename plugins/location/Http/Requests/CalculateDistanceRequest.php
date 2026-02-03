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

namespace Plugins\Location\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateDistanceRequest extends FormRequest
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
            'lat1' => 'required|numeric|between:-90,90',
            'lng1' => 'required|numeric|between:-180,180',
            'lat2' => 'required|numeric|between:-90,90',
            'lng2' => 'required|numeric|between:-180,180',
            'driver' => 'nullable|string',
        ];
    }

    /**
     * 获取验证错误消息.
     */
    public function messages(): array
    {
        return [
            'lat1.required' => '点1纬度不能为空',
            'lat1.numeric' => '点1纬度必须是数字',
            'lat1.between' => '点1纬度必须在 -90 到 90 之间',
            'lng1.required' => '点1经度不能为空',
            'lng1.numeric' => '点1经度必须是数字',
            'lng1.between' => '点1经度必须在 -180 到 180 之间',
            'lat2.required' => '点2纬度不能为空',
            'lat2.numeric' => '点2纬度必须是数字',
            'lat2.between' => '点2纬度必须在 -90 到 90 之间',
            'lng2.required' => '点2经度不能为空',
            'lng2.numeric' => '点2经度必须是数字',
            'lng2.between' => '点2经度必须在 -180 到 180 之间',
        ];
    }

    public function attributes(): array
    {
        return [
            'lat1' => '点1纬度',
            'lng1' => '点1经度',
            'lat2' => '点2纬度',
            'lng2' => '点2经度',
            'driver' => '驱动名称',
        ];
    }
}
