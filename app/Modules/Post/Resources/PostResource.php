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

use Exception;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Log;

class PostResource extends JsonResource
{

    public function toArray($request)
    {
        // 获取已加载的关联数据
        $author = $this->whenLoaded('user');
        $files = $this->whenLoaded('files') ?: collect([]);
        $mentions = $this->whenLoaded('mentions') ?: collect([]);
        $topics = $this->whenLoaded('topics') ?: collect([]);

        // 设置用户状态（如果有关联数据）
        $this->setUserInteractionStatus($request);

        // 预先过滤不同类型的文件，避免重复过滤
        $images = $files instanceof \Illuminate\Database\Eloquent\Collection
            ? $files->filter(function ($file) {
                return $file->type === \App\Modules\File\Models\File::TYPE_IMAGE;
            })->values()
            : collect([]);
        
        $videos = $files instanceof \Illuminate\Database\Eloquent\Collection
            ? $files->where('type', 'video')->values()
            : collect([]);

        $documents = $files instanceof \Illuminate\Database\Eloquent\Collection
            ? $files->where('type', 'document')->values()
            : collect([]);

        $coverImage = $files instanceof \Illuminate\Database\Eloquent\Collection
            ? $files->first(function ($file) {
                return $file->type === \App\Modules\File\Models\File::TYPE_COVER;
            })
            : null;

        return [
            'id' => (string) $this->id,
            'type' => $this->type ?? 'post',  // 内容类型：post, article, question, thread, image, video
            'title' => $this->title,  // 标题（文章、问题、话题等类型使用）
            // 转发时，content 存储转发者的转发理由（可选）；普通动态时，content 存储动态内容
            //'content' => $this->content,
            // HTML渲染的内容（包含@用户和#话题的超链接）
            'contentHtml' => $this->content_html,
            // 转发ID（有值表示是转发，前端通过此字段判断）
            'repostId' => $this->repost_id ? (string) $this->repost_id : null,
            // 原动态信息（通过关联获取，包含原动态的完整内容）
            'originalPost' => $this->when($this->repost_id && $this->relationLoaded('originalPost'), function () {
                return new PostResource($this->originalPost);
            }),
            // 转发链条：追溯完整的转发路径（最多5层，避免性能问题）
            'repostChain' => $this->when($this->repost_id, function () {
                return $this->buildRepostChain();
            }),
            'contentHtmlPreview' => Str::limit(strip_tags($this->content_html ?? ''), 300),
            'contentExceeded' => Str::length(strip_tags($this->content_html ?? '')) > 300,
            'status' => $this->status,
            'statusText' => $this->status_text, // 使用 Accessor
            'visibility' => $this->visibility,
            'visibilityText' => $this->visibility_text, // 使用 Accessor
            'location' => $this->location_id ? $this->location : null, // 当没有location_id时不返回location字段
            'isTop' => (bool) ($this->is_top ?? false),
            'isEssence' => (bool) ($this->is_essence ?? false),
            'isRecommend' => (bool) ($this->is_recommend ?? false),
            'author' => $author ? [
                'id' => (string) $author->id,
                'username' => $author->username,
                'nickname' => $author->nickname,
                'avatarUrl' => $author->avatar_url,
            ] : null,
            // 时间字段 - 使用 Laravel 原生的人类可读时间格式
            'createdAtHuman' => $this->created_at ? $this->created_at->locale('zh_CN')->diffForHumans() : '',
            'likeCount' => $this->likes_count ?? $this->like_count ?? $this->likes->count() ?? 0,
            'commentCount' => $this->comments_count ?? $this->comment_count ?? $this->comments->count() ?? 0,
            'collectCount' => $this->collects_count ?? $this->collect_count ?? $this->collects->count() ?? 0,
            'isLiked' => $this->isLiked ?? $this->is_liked ?? false,
            'isCollected' => $this->isCollected ?? $this->is_favorited ?? false,
            // 使用Policy动态检查权限
            'canEdit' => $request->user() ? $request->user()->can('update', $this->resource) : false,
            'canDelete' => $request->user() ? $request->user()->can('delete', $this->resource) : false,
            'files' => ($files instanceof \Illuminate\Database\Eloquent\Collection && $files->isNotEmpty())
                ? \App\Modules\File\Resources\FileResource::collection($files)
                : [],
            'images' => $images->isNotEmpty()
                ? \App\Modules\File\Resources\FileResource::collection($images)
                : [],
            'videos' => $videos->isNotEmpty()
                ? \App\Modules\File\Resources\FileResource::collection($videos)
                : [],
            'coverImage' => $coverImage ? new \App\Modules\File\Resources\FileResource($coverImage) : null,
            'documents' => $documents->isNotEmpty()
                ? \App\Modules\File\Resources\FileResource::collection($documents)
                : [],
        ];
    }

    /**
     * 设置用户交互状态（点赞、收藏等）
     * 修复SSR认证问题：正确区分"未认证"和"未点赞"状态
     */
    protected function setUserInteractionStatus($request): void
    {
        $user = $request->user();

        // 设置点赞状态 - 修复SSR认证问题
        if ($this->resource->relationLoaded('likes')) {
            // 如果有关联数据，检查用户是否已点赞（支持SSR环境）
            $this->resource->isLiked = $user ? $this->resource->likes->contains('user_id', $user->id) : false;
        } else {
            // 如果没有关联数据，说明查询时用户未认证，设为false
            $this->resource->isLiked = false;
        }

        // 设置收藏状态 - 同样的逻辑修复
        if ($this->resource->relationLoaded('collects')) {
            // 如果有关联数据，检查用户是否已收藏（支持SSR环境）
            $this->resource->isCollected = $user ? $this->resource->collects->contains('user_id', $user->id) : false;
        } else {
            // 如果没有关联数据，说明查询时用户未认证，设为false
            $this->resource->isCollected = false;
        }
    }

    /**
     * 构建转发链条
     * 追溯完整的转发路径，最多5层避免性能问题和无限循环
     */
    protected function buildRepostChain(): array
    {
        $chain = [];
        $currentPost = $this->resource;
        $depth = 0;
        $maxDepth = 5; // 最大追溯深度
        $visited = []; // 防止循环引用

        while ($currentPost->repost_id && $depth < $maxDepth && !in_array($currentPost->repost_id, $visited)) {
            $visited[] = $currentPost->repost_id;

            // 尝试从已加载的关联中获取原动态
            $originalPost = null;
            if ($currentPost->relationLoaded('originalPost') && $currentPost->originalPost) {
                $originalPost = $currentPost->originalPost;
            } else {
                // 如果没有预加载，则查询数据库（仅在必要时）
                try {
                    $originalPost = \App\Modules\Post\Models\Post::with('user:id,username,nickname,avatar')
                        ->select('id', 'user_id', 'content', 'repost_id', 'created_at')
                        ->find($currentPost->repost_id);
                } catch (Exception $e) {
                    Log::warning('Failed to load repost chain item', [
                        'post_id' => $currentPost->id,
                        'repost_id' => $currentPost->repost_id,
                        'error' => $e->getMessage()
                    ]);
                    break;
                }
            }

            if (!$originalPost) {
                break;
            }

            $chain[] = [
                'id' => (string) $originalPost->id,
                'user' => [
                    'id' => (string) $originalPost->user_id,
                    'username' => $originalPost->user->username ?? '未知用户',
                    'nickname' => $originalPost->user->nickname ?? '未知用户',
                    'avatarUrl' => $originalPost->user->avatar_url,
                ],
                'content' => Str::limit(strip_tags($originalPost->content ?? ''), 100),
                'createdAt' => $originalPost->created_at?->format('Y-m-d H:i:s'),
            ];

            $currentPost = $originalPost;
            $depth++;
        }

        return array_reverse($chain); // 从最早的转发开始
    }

}
