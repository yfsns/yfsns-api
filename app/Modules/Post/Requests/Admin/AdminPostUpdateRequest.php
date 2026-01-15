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

namespace App\Modules\Post\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 管理员更新动态请求类
 */
class AdminPostUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:10000',
            'type' => 'nullable|string|in:post,article,question,thread,image,video',
            'visibility' => 'nullable|integer|in:1,2,3,4',
            'status' => 'nullable|integer|in:0,1,2,3',
            'images' => 'nullable|array|max:50',
            'images.*' => 'string|url|regex:/\.(jpg|jpeg|png|gif|webp)$/i|distinct',
            'remark' => 'nullable|string|max:500',
        ];
    }

    /**
     * 获取验证错误的自定义属性
     */
    public function attributes(): array
    {
        return [
            'title' => '标题',
            'content' => '内容',
            'type' => '内容类型',
            'visibility' => '可见性',
            'status' => '审核状态',
            'images' => '图片列表',
            'images.*' => '图片',
            'remark' => '审核备注',
        ];
    }

    /**
     * 获取验证错误的自定义消息
     */
    public function messages(): array
    {
        return [
            // 内容相关
            'title.max' => '标题长度不能超过255个字符',
            'content.max' => '内容长度不能超过10000个字符',

            // 类型相关
            'type.in' => '内容类型只能是：动态、文章、问题、话题、图片或视频',

            // 可见性相关
            'visibility.in' => '可见性设置不正确',

            // 状态相关
            'status.in' => '审核状态只能是：0（草稿）、1（已发布）、2（审核中）或3（已拒绝）',
            'status.integer' => '审核状态必须是整数',

            // 图片相关
            'images.array' => '图片列表格式不正确',
            'images.max' => '最多只能上传50张图片',
            'images.*.url' => '图片链接格式不正确',
            'images.*.regex' => '图片格式必须是：jpg、jpeg、png、gif或webp',
            'images.*.distinct' => '图片不能重复',

            // 审核相关
            'remark.max' => '审核备注长度不能超过500个字符',
        ];
    }

    /**
     * 获取每个参数的详细说明（用于接口文档）
     */
    public function bodyParameters(): array
    {
        return [
            // 内容参数
            'title' => [
                'description' => '内容标题，最多255字符',
                'example' => '更新后的公告标题',
            ],
            'content' => [
                'description' => '内容文本，最多10000字符',
                'example' => '这是更新后的系统公告内容...',
            ],
            'type' => [
                'description' => '内容类型：post（动态）、article（文章）、question（问题）、thread（话题）、image（图片）、video（视频）',
                'example' => 'post',
            ],
            'visibility' => [
                'description' => '可见性：1（公开）、2（好友可见）、3（私密）、4（粉丝可见）',
                'example' => 1,
            ],
            'status' => [
                'description' => '审核状态：0（草稿）、1（已发布）、2（审核中）、3（已拒绝）',
                'example' => 1,
            ],
            'images' => [
                'description' => '图片链接数组，最多50张',
                'example' => ['https://example.com/image1.jpg', 'https://example.com/image2.png'],
            ],
            'remark' => [
                'description' => '审核备注，最多500字符',
                'example' => '管理员更新的内容',
            ],
        ];
    }
}
