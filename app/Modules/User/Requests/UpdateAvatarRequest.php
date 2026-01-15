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

class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'uploaded_file', // 更安全的上传文件验证
                'image', // 确保是图片文件
                'max:2048', // 最大2MB
                'mimes:jpeg,jpg,png,gif,webp', // 允许的图片格式
                'dimensions:min_width=50,min_height=50,max_width=2048,max_height=2048' // 图片尺寸限制
            ],
        ];
    }

    /**
     * 获取验证错误的自定义属性
     */
    public function attributes(): array
    {
        return [
            'avatar' => '头像文件',
        ];
    }

    /**
     * 获取验证错误的自定义消息
     */
    public function messages(): array
    {
        return [
            'avatar.required' => '请选择头像文件',
            'avatar.uploaded_file' => '头像文件上传失败',
            'avatar.image' => '上传的文件必须是图片格式',
            'avatar.max' => '头像文件大小不能超过2MB',
            'avatar.mimes' => '头像文件格式必须是：jpeg、jpg、png、gif或webp',
            'avatar.dimensions' => '头像图片尺寸不符合要求（最小50x50，最大2048x2048）',
        ];
    }

    /**
     * 获取每个参数的详细说明（用于接口文档）
     */
    public function bodyParameters(): array
    {
        return [
            'avatar' => [
                'description' => '用户头像文件，支持jpeg、jpg、png、gif、webp格式，大小不超过2MB，尺寸在50x50到2048x2048之间。',
                'example' => '(file) avatar.jpg',
            ],
        ];
    }}
