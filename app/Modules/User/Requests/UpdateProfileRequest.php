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

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nickname' => [
                'sometimes',
                'string',
                'min:1',
                'max:50',
                'regex:/^[\p{L}\p{N}\s\-_]+$/u' // 允许字母、数字、空格、连字符、下划线
            ],
            'avatar' => [
                'sometimes',
                'string',
                'url', // 验证为有效的URL
                'max:500'
            ],
            'gender' => [
                'nullable',
                'in:男,女,保密'
            ],
            'birthday' => [
                'nullable',
                'date',
                'before:today', // 生日不能是未来日期
                'after:1900-01-01' // 合理的历史日期范围
            ],
            'bio' => [
                'nullable',
                'string',
                'max:200',
                'regex:/^[\p{L}\p{N}\p{P}\p{Z}\p{S}]+$/u' // 允许字母、数字、标点、空白、符号
            ],
        ];
    }

    /**
     * 获取验证错误的自定义属性
     */
    public function attributes(): array
    {
        return [
            'nickname' => '昵称',
            'avatar' => '头像',
            'gender' => '性别',
            'birthday' => '生日',
            'bio' => '个人简介',
        ];
    }

    /**
     * 获取验证错误的自定义消息
     */
    public function messages(): array
    {
        return [
            'nickname.min' => '昵称长度不能小于1个字符',
            'nickname.max' => '昵称长度不能超过50个字符',
            'nickname.regex' => '昵称只能包含字母、数字、空格、连字符和下划线',
            'avatar.url' => '头像必须是有效的URL地址',
            'avatar.max' => '头像URL长度不能超过500个字符',
            'gender.in' => '性别只能选择：男、女或保密',
            'birthday.before' => '生日不能是未来日期',
            'birthday.after' => '生日日期不合理',
            'bio.max' => '个人简介长度不能超过200个字符',
            'bio.regex' => '个人简介包含无效字符',
        ];
    }

    /**
     * 获取每个参数的详细说明（用于接口文档）
     */
    public function bodyParameters(): array
    {
        return [
            'nickname' => [
                'description' => '用户昵称，支持中文、英文、数字、空格、连字符和下划线，1-50字符。',
                'example' => '张三',
            ],
            'avatar' => [
                'description' => '用户头像URL地址，必须是可访问的图片链接。',
                'example' => 'https://example.com/avatar.jpg',
            ],
            'gender' => [
                'description' => '用户性别，可选值：男、女、保密。',
                'example' => '男',
            ],
            'birthday' => [
                'description' => '用户生日，格式为YYYY-MM-DD，不能是未来日期。',
                'example' => '1990-01-01',
            ],
            'bio' => [
                'description' => '用户个人简介，最多200字符。',
                'example' => '热爱编程，喜欢分享技术。',
            ],
        ];
    }}
