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

namespace App\Modules\Post\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StoryResource extends JsonResource
{
    public function toArray($request)
    {
        // 设置用户交互状态
        $this->setUserInteractionStatus($request);

        return [
            'id' => (string) $this->id,
            'type' => 'story',
            'title' => $this->title,
            'contentHtml' => $this->content_html,
            'status' => $this->status,
            'statusText' => $this->status_text,
            'visibility' => $this->visibility,
            'visibilityText' => $this->visibility_text,
            'location' => $this->location_id ? $this->location : null,
            'isTop' => (bool) ($this->is_top ?? false),
            'isEssence' => (bool) ($this->is_essence ?? false),
            'isRecommend' => (bool) ($this->is_recommend ?? false),
            'author' => $this->whenLoaded('user', function () {
                return [
                    'id' => (string) $this->user->id,
                    'username' => $this->user->username,
                    'nickname' => $this->user->nickname,
                    'avatarUrl' => $this->user->avatar_url,
                ];
            }),
            'createdAtHuman' => $this->created_at ? $this->created_at->locale('zh_CN')->diffForHumans() : '',
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
            'likeCount' => $this->likes_count ?? $this->likes->count() ?? 0,
            'commentCount' => $this->comments_count ?? $this->comments->count() ?? 0,
            'collectCount' => $this->collects_count ?? $this->collects->count() ?? 0,
            'isLiked' => $this->isLiked ?? $this->is_liked ?? false,
            'isCollected' => $this->isCollected ?? $this->is_favorited ?? false,
            'canEdit' => false, // 故事不支持编辑
            'canDelete' => $request->user() ? $request->user()->can('delete', $this->resource) : false,
            'images' => $this->whenLoaded('files', function () {
                return $this->files->filter(function ($file) {
                    return $file->type === \App\Modules\File\Models\File::TYPE_IMAGE;
                })->map(function ($file) {
                    return [
                        'fileId' => $file->id,
                        'name' => $file->name,
                        'url' => $file->url, // 使用File模型的url属性获取完整URL
                        'thumbnail' => $file->thumbnail,
                        'size' => $file->size,
                        'mimeType' => $file->mime_type,
                        'width' => $file->width ?? $this->getImageWidth($file),
                        'height' => $file->height ?? $this->getImageHeight($file),
                    ];
                });
            }),
            'coverImage' => $this->whenLoaded('files', function () {
                $cover = $this->files->first(function ($file) {
                    return $file->type === \App\Modules\File\Models\File::TYPE_COVER;
                });
                return $cover ? [
                    'fileId' => $cover->id,
                    'name' => $cover->name,
                    'url' => $cover->url, // 使用File模型的url属性获取完整URL
                    'size' => $cover->size,
                    'mimeType' => $cover->mime_type,
                ] : null;
            }),
            // @用户列表
            'mentions' => $this->whenLoaded('mentions', function () {
                return $this->mentions->map(function ($mention) {
                    return [
                        'userId' => (string) $mention->user_id,
                        'username' => $mention->username,
                        'nickname' => $mention->nickname_at_time,
                        'avatarUrl' => $mention->user->avatar_url,
                    ];
                });
            }),
            // #话题列表
            'topics' => $this->whenLoaded('topics', function () {
                return $this->topics->map(function ($topic) {
                    return [
                        'id' => (string) $topic->id,
                        'name' => $topic->name,
                        'cover' => $topic->cover,
                        'position' => $topic->pivot->position ?? 0,
                    ];
                });
            }),
        ];
    }

    /**
     * 获取图片宽度
     */
    protected function getImageWidth($file): ?int
    {
        return $file->metadata['width'] ?? null;
    }

    /**
     * 获取图片高度
     */
    protected function getImageHeight($file): ?int
    {
        return $file->metadata['height'] ?? null;
    }

    /**
     * 设置用户交互状态
     */
    protected function setUserInteractionStatus($request): void
    {
        $user = $request->user();

        // 设置点赞状态
        if ($this->resource->relationLoaded('likes')) {
            $this->resource->isLiked = $user ? $this->resource->likes->contains('user_id', $user->id) : false;
        } else {
            $this->resource->isLiked = false;
        }

        // 设置收藏状态
        if ($this->resource->relationLoaded('collects')) {
            $this->resource->isCollected = $user ? $this->resource->collects->contains('user_id', $user->id) : false;
        } else {
            $this->resource->isCollected = false;
        }
    }
}