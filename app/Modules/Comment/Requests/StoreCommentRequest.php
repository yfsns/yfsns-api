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

namespace App\Modules\Comment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_id' => [
                'required',
                'integer',
                'min:1'
            ],
            'target_type' => [
                'required',
                'string',
                'in:post,article,comment'
            ],
            'content' => [
                'nullable', // 允许为空
                'string',
                'max:5000' // 增加最大长度限制
            ],
            'content_type' => [
                'nullable', // 允许为空
                'string',
                'in:text,image,video'
            ],
            'images' => [
                'sometimes',
                'array',
                'max:20' // 增加最多图片数量
            ],
            'images.*' => [
                'string' // 简化验证，只要求是字符串
            ],
            // 'video_url' => [], // 完全移除验证
            'parent_id' => [
                'nullable',
                'integer'
            ],
            'mentions' => [
                'sometimes',
                'array',
                'max:50' // 增加最多@用户数量
            ],
            'mentions.*' => [
                'integer' // 简化验证，不检查用户是否存在
            ],
            'topics' => [
                'sometimes',
                'array',
                'max:20' // 增加最多话题数量
            ],
            'topics.*' => [
                'integer' // 简化验证，不检查话题是否存在
            ],
        ];
    }

    /**
     * 获取验证错误的自定义属性
     */
    public function attributes(): array
    {
        return [
            'target_id' => '目标ID',
            'target_type' => '目标类型',
            'content' => '评论内容',
            'content_type' => '内容类型',
            'images' => '图片列表',
            'images.*' => '图片',
            // 'video_url' => '视频链接',
            'parent_id' => '父评论ID',
            'mentions' => '@用户列表',
            'mentions.*' => '@用户',
            'topics' => '话题列表',
            'topics.*' => '话题',
        ];
    }

    /**
     * 获取验证错误的自定义消息
     */
    public function messages(): array
    {
        return [
            'target_id.required' => '请选择要评论的目标',
            'target_id.integer' => '目标ID必须是整数',
            'target_id.min' => '目标ID不能小于1',
            'target_type.required' => '请选择目标类型',
            'target_type.in' => '目标类型只能是：文章、帖子或评论',
            'content.max' => '评论内容不能超过5000个字符',
            'content_type.in' => '内容类型只能是：文本、图片或视频',
            'images.array' => '图片列表格式不正确',
            'images.max' => '最多只能上传20张图片',
            // 'video_url.string' => '视频链接格式不正确', // 移除验证
            'parent_id.integer' => '父评论ID必须是整数',
            'mentions.array' => '@用户列表格式不正确',
            'mentions.max' => '最多只能@50个用户',
            'mentions.*.integer' => '@用户ID必须是整数',
            'topics.array' => '话题列表格式不正确',
            'topics.max' => '最多只能关联20个话题',
            'topics.*.integer' => '话题ID必须是整数',
        ];
    }

    /**
     * 获取每个参数的详细说明（用于接口文档）
     */
    public function bodyParameters(): array
    {
        return [
            'target_id' => [
                'description' => '要评论的目标ID（文章ID、帖子ID或父评论ID）',
                'example' => 123,
            ],
            'target_type' => [
                'description' => '目标类型：post（帖子）、article（文章）或comment（评论）',
                'example' => 'post',
            ],
            'content' => [
                'description' => '评论文本内容，最多5000字符，可选',
                'example' => '这篇文章写得很好！',
            ],
            'content_type' => [
                'description' => '内容类型：text（文本）、image（图片）或video（视频），可选',
                'example' => 'text',
            ],
            'images' => [
                'description' => '图片链接数组，最多20张，可选',
                'example' => ['https://example.com/image1.jpg', 'https://example.com/image2.png'],
            ],
            // 'video_url' => [
            //     'description' => '视频链接，可选',
            //     'example' => 'https://example.com/video.mp4',
            // ],
            'parent_id' => [
                'description' => '父评论ID，用于回复评论，可选',
                'example' => 456,
            ],
            'mentions' => [
                'description' => '@用户ID数组，最多50个，可选',
                'example' => [1, 2, 3],
            ],
            'topics' => [
                'description' => '关联话题ID数组，最多20个，可选',
                'example' => [10, 20],
            ],
        ];
    }

    /**
     * 准备验证数据.
     * 转换前端驼峰格式字段为后端下划线格式.
     */
    protected function prepareForValidation(): void
    {
        // 转换驼峰格式字段为下划线格式
        $this->merge([
            'target_id' => $this->targetId ?? $this->target_id,
            'target_type' => $this->targetType ?? $this->target_type,
            'content_type' => $this->contentType ?? $this->content_type,
            // 'video_url' => $this->videoUrl ?? $this->video_url, // 移除处理
            'parent_id' => $this->parentId ?? $this->parent_id,
        ]);
    }
}
