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

namespace App\Modules\Search\Resources;

use const ENT_QUOTES;

use function get_class;

use Illuminate\Http\Resources\Json\JsonResource;

use function in_array;

class SearchResultResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        // 获取搜索关键词（用于高亮）
        $searchQuery = $request->input('q') ?? $request->input('query') ?? '';

        $data = [
            'id' => $this->id,
            'type' => $this->getResourceType(),
            'title' => $this->getTitle(),
            'content' => $this->getContent(),
            'url' => $this->getUrl(),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
            'stats' => $this->getStats(),
            'tags' => $this->getTags(),
        ];

        // 添加高亮版本的标题和内容
        if ($searchQuery) {
            $data['titleHighlight'] = $this->highlightKeyword($data['title'], $searchQuery);
            $data['contentHighlight'] = $this->highlightKeyword($data['content'], $searchQuery);
        }

        // 根据类型添加特定字段
        $type = $this->getResourceType();

        // 如果是用户类型，添加头像等字段
        if ($type === 'user') {
            $data['username'] = $this->username;
            $data['nickname'] = $this->nickname;
            $data['avatarUrl'] = $this->avatar_url;
            $data['bio'] = $this->bio;
        }

        // 如果有关联的user字段（如post、comment），添加作者信息
        if ($this->user) {
            $data['user'] = [
                'id' => (string) $this->user->id,
                'username' => $this->user->username,
                'nickname' => $this->user->nickname,
                'avatarUrl' => $this->user->avatar_url,
            ];
        }

        // 如果是话题或群组，添加封面
        if (in_array($type, ['topic', 'group']) && isset($this->cover)) {
            $data['cover'] = $this->cover;
            $data['coverUrl'] = $this->cover_url ?? $this->cover;
        }

        return $data;
    }

    /**
     * 获取资源类型.
     */
    protected function getResourceType(): string
    {
        $class = get_class($this->resource);

        return match (true) {
            str_contains($class, 'Post') => 'post',
            str_contains($class, 'User') => 'user',
            str_contains($class, 'Comment') => 'comment',
            str_contains($class, 'Topic') => 'topic',
            str_contains($class, 'Group') => 'group',
            default => 'unknown'
        };
    }

    /**
     * 获取标题.
     */
    protected function getTitle(): ?string
    {
        return match ($this->getResourceType()) {
            'post' => $this->title,
            'user' => $this->nickname ?? $this->username,
            'comment' => mb_substr($this->content, 0, 50) . '...',
            'topic' => $this->name,
            'group' => $this->name,
            default => null
        };
    }

    /**
     * 获取内容.
     */
    protected function getContent(): ?string
    {
        return match ($this->getResourceType()) {
            'post' => $this->content,
            'user' => $this->bio,
            'comment' => $this->content,
            'topic' => $this->description,
            'group' => $this->description,
            default => null
        };
    }

    /**
     * 获取URL.
     */
    protected function getUrl(): string
    {
        return match ($this->getResourceType()) {
            'post' => "/posts/{$this->id}",
            'user' => "/users/{$this->id}",
            'comment' => "/comments/{$this->id}",
            'topic' => "/topics/{$this->id}",
            'group' => "/groups/{$this->id}",
            default => '#'
        };
    }

    /**
     * 获取统计信息（驼峰格式）.
     */
    protected function getStats(): array
    {
        return match ($this->getResourceType()) {
            'post' => [
                'likesCount' => $this->likes_count ?? 0,
                'commentsCount' => $this->comments_count ?? 0,
                'sharesCount' => $this->shares_count ?? 0,
            ],
            'user' => [
                'followersCount' => $this->followers_count ?? 0,
                'followingCount' => $this->following_count ?? 0,
                'postsCount' => $this->posts_count ?? 0,
            ],
            'comment' => [
                'likesCount' => $this->likes_count ?? 0,
                'repliesCount' => $this->replies_count ?? 0,
            ],
            'topic' => [
                'postsCount' => $this->posts_count ?? 0,
                'followersCount' => $this->followers_count ?? 0,
            ],
            'group' => [
                'membersCount' => $this->members_count ?? 0,
                'postsCount' => $this->posts_count ?? 0,
            ],
            default => []
        };
    }

    /**
     * 获取标签.
     */
    protected function getTags(): array
    {
        return match ($this->getResourceType()) {
            'post' => $this->topics?->pluck('name')->toArray() ?? [],
            'user' => $this->skills ?? [],
            'topic' => $this->tags ?? [],
            'group' => $this->tags ?? [],
            default => []
        };
    }

    /**
     * 高亮关键字（将匹配的关键字用HTML标签包裹）.
     *
     * @param null|string $text    要处理的文本
     * @param string      $keyword 搜索关键词
     */
    protected function highlightKeyword(?string $text, string $keyword): ?string
    {
        if (! $text || ! $keyword) {
            return $text;
        }

        // 转义HTML特殊字符，防止XSS
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // 使用<span>标签包裹匹配的关键字，添加CSS类名
        // 前端可以通过CSS设置红色：.search-highlight { color: red; }
        return preg_replace(
            '/(' . preg_quote($keyword, '/') . ')/iu',
            '<span class="search-highlight">$1</span>',
            $text
        );
    }
}
