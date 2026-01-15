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

namespace App\Modules\Search\Services;

use App\Modules\Comment\Models\Comment;
use App\Modules\Post\Models\Post;
use App\Modules\Topic\Models\Topic;
use App\Modules\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SearchService
{
    /**
     * 全局搜索.
     */
    public function globalSearch(string $query, array $filters = [], int $limit = 20): Collection
    {
        $results = collect();

        // 搜索动态
        $posts = $this->searchPosts($query, $filters, $limit);
        $results->put('posts', $posts);

        // 搜索用户
        $users = $this->searchUsers($query, $filters, $limit);
        $results->put('users', $users);

        // 搜索评论
        $comments = $this->searchComments($query, $filters, $limit);
        $results->put('comments', $comments);

        // 搜索话题
        $topics = $this->searchTopics($query, $filters, $limit);
        $results->put('topics', $topics);

        return $results;
    }

    /**
     * 搜索动态
     */
    public function searchPosts(string $searchQuery, array $filters = [], int $limit = 20): Collection
    {
        $query = Post::query()
            ->with([
                'user' => function ($query) {
                    $query->withEssentialFields('status');
                },
            ])
            ->where('status', Post::STATUS_PUBLISHED)
            ->where(function ($q) use ($searchQuery): void {
                $q->where('title', 'like', "%{$searchQuery}%")
                    ->orWhere('content', 'like', "%{$searchQuery}%");
            });

        // 应用过滤器
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // 暂时移除 topic 过滤，因为 Post 模型没有 topics 关系
        // if (isset($filters['topic_id'])) {
        //     $query->whereHas('topics', function ($q) use ($filters) {
        //         $q->where('topic_id', $filters['topic_id']);
        //     });
        // }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 搜索用户
     * 优化：返回带统计字段的完整用户数据.
     */
    public function searchUsers(string $searchQuery, array $filters = [], int $limit = 20): Collection
    {
        $query = User::query()
            ->active() // 使用 scope
            ->where(function ($q) use ($searchQuery): void {
                $q->where('username', 'like', "%{$searchQuery}%")
                    ->orWhere('nickname', 'like', "%{$searchQuery}%")
                    ->orWhere('bio', 'like', "%{$searchQuery}%");
            })
            ->withCount([
                'followers',
                'following',
                'posts' => function ($query): void {
                    $query->where('status', 'published');
                },
            ]);

        // 应用过滤器
        if (isset($filters['is_verified'])) {
            $query->where('is_verified', $filters['is_verified']);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 搜索评论.
     */
    public function searchComments(string $searchQuery, array $filters = [], int $limit = 20): Collection
    {
        $query = Comment::query()
            ->with([
                'user' => function ($query) {
                    $query->withEssentialFields('status');
                },
            ])
            ->where('status', Comment::STATUS_NORMAL)
            ->where('content', 'like', "%{$searchQuery}%");

        // 应用过滤器
        if (isset($filters['target_type'])) {
            $query->where('target_type', $filters['target_type']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 搜索话题
     * 优化：返回带统计字段的完整话题数据.
     */
    public function searchTopics(string $searchQuery, array $filters = [], int $limit = 20): Collection
    {
        $query = Topic::query()
            ->where('status', Topic::STATUS_ACTIVE)
            ->where(function ($q) use ($searchQuery): void {
                $q->where('name', 'like', "%{$searchQuery}%")
                    ->orWhere('description', 'like', "%{$searchQuery}%");
            });
        // 注意：post_count 和 follower_count 是 topics 表中的计数器字段，无需 withCount

        return $query->orderBy('post_count', 'desc') // 按热度排序
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }


    /**
     * 获取搜索建议.
     */
    public function getSuggestions(string $searchQuery, int $limit = 10): Collection
    {
        $suggestions = collect();

        // 用户建议
        $userSuggestions = User::where('username', 'like', "{$searchQuery}%")
            ->orWhere('nickname', 'like', "{$searchQuery}%")
            ->limit($limit)
            ->pluck('username', 'id');
        $suggestions->put('users', $userSuggestions);

        // 话题建议
        $topicSuggestions = Topic::where('name', 'like', "{$searchQuery}%")
            ->limit($limit)
            ->pluck('name', 'id');
        $suggestions->put('topics', $topicSuggestions);

        return $suggestions;
    }

    /**
     * 获取热门搜索词.
     */
    public function getHotSearches(int $limit = 20): Collection
    {
        // 尝试从数据库获取真实的热门搜索词
        $hotWords = \App\Modules\Search\Models\HotSearchWord::getActiveHotWords($limit);
        if ($hotWords->isNotEmpty()) {
            return $hotWords->pluck('keyword');
        }

        // 返回示例数据作为备选
        return collect([
            '人工智能',
            '机器学习',
            '区块链',
            '元宇宙',
            'Web3',
            'ChatGPT',
            '新能源',
            '电动汽车',
            '5G技术',
            '云计算',
        ])->take($limit);
    }
}
