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

namespace App\Modules\Topic\Services;

use App\Modules\Topic\Models\Topic;
use App\Modules\Topic\Models\TopicReviewLog;
use App\Modules\Review\Services\ReviewService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function in_array;

use InvalidArgumentException;
use Throwable;

class TopicService
{

    public function __construct()
    {
    }

    /**
     * 获取热门话题.
     *
     * @param int $limit 限制数量
     * @param int $days  天数限制
     */
    public function getHotTopics(int $limit = 20, int $days = 7): array
    {
        return Topic::select([
            'id',
            'name',
            'description',
            'cover',
            'post_count',
            'follower_count',
        ])
            ->where('status', 1)
            ->orderBy('post_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * 获取推荐话题.
     *
     * @param int $limit 限制数量
     */
    public function getRecommendedTopics(int $limit = 10): array
    {
        $cacheKey = "recommended_topics_{$limit}";

        return Cache::remember($cacheKey, 1800, function () use ($limit) { // 缓存30分钟
            return Topic::select([
                'id',
                'name',
                'description',
                'cover',
                'post_count',
                'follower_count',
            ])
                ->where('status', Topic::STATUS_PUBLISHED)
                ->orderBy('post_count', 'desc')
                ->orderBy('follower_count', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($topic) {
                    return [
                        'id' => $topic->id,
                        'name' => $topic->name,
                        'description' => $topic->description,
                        'cover' => $topic->cover,
                        'postCount' => $topic->post_count,
                        'followerCount' => $topic->follower_count,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * 获取话题详情（使用ID）.
     *
     * @param int $id 话题ID
     */
    public function getTopicDetailById(int $id): ?array
    {
        $topic = Topic::where('id', $id)
            ->where('status', 1)
            ->first();

        if (! $topic) {
            return null;
        }

        // 获取该话题下的动态数量
        $postCount = DB::table('post_topics')
            ->join('posts', 'post_topics.post_id', '=', 'posts.id')
            ->where('post_topics.topic_id', $topic->id)
            ->where('posts.status', 1)
            ->whereNull('posts.deleted_at')
            ->count();

        return [
            'id' => $topic->id,
            'name' => $topic->name,
            'description' => $topic->description,
            'cover' => $topic->cover,
            'postCount' => $postCount,
            'followerCount' => $topic->follower_count,
            'createdAt' => $topic->created_at?->toISOString(),
            'updatedAt' => $topic->updated_at?->toISOString(),
        ];
    }

    /**
     * 获取话题详情（使用名称，保留兼容）.
     *
     * @param string $topicName 话题名称
     */
    public function getTopicDetail(string $topicName): ?array
    {
        $topic = Topic::where('name', $topicName)
            ->where('status', 1)
            ->first();

        if (! $topic) {
            return null;
        }

        return $this->getTopicDetailById($topic->id);
    }

    /**
     * 获取话题趋势（使用ID）.
     *
     * @param int $id   话题ID
     * @param int $days 天数
     */
    public function getTopicTrendsById(int $id, int $days = 7): array
    {
        return DB::table('post_topics')
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
            ])
            ->where('topic_id', $id)
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * 获取话题趋势（使用名称，保留兼容）.
     *
     * @param string $topicName 话题名称
     * @param int    $days      天数
     */
    public function getTopicTrends(string $topicName, int $days = 7): array
    {
        $topic = Topic::where('name', $topicName)->first();
        if (! $topic) {
            return [];
        }

        return $this->getTopicTrendsById($topic->id, $days);
    }

    /**
     * 搜索话题.
     *
     * @param string $keyword 关键词
     * @param int    $limit   限制数量
     */
    public function searchTopics(string $keyword, int $limit = 10): array
    {
        return Topic::select(['id', 'name', 'description', 'post_count'])
            ->where('status', 1)
            ->where(function ($query) use ($keyword): void {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            })
            ->orderBy('post_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * 获取话题下的动态列表（使用ID）.
     *
     * @param int $id      话题ID
     * @param int $perPage 每页数量
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getTopicPostsById(int $id, int $perPage = 20)
    {
        return DB::table('posts')
            ->join('post_topics', 'posts.id', '=', 'post_topics.post_id')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->where('post_topics.topic_id', $id)
            ->where('posts.status', 1)
            ->whereNull('posts.deleted_at')
            ->select([
                'posts.id',
                'posts.content',
                'posts.created_at',
                'users.id as author_id',
                'users.username as author_username',
                'users.nickname as author_nickname',
                'users.avatar as author_avatar',
            ])
            ->orderBy('posts.created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * 获取话题下的动态列表（使用名称，保留兼容）.
     *
     * @param string $topicName 话题名称
     * @param int    $perPage   每页数量
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getTopicPosts(string $topicName, int $perPage = 20)
    {
        $topic = Topic::where('name', $topicName)->where('status', 1)->first();
        if (! $topic) {
            return collect([])->paginate($perPage);
        }

        return $this->getTopicPostsById($topic->id, $perPage);
    }

    /**
     * 获取话题统计信息.
     *
     * @param int $topicId 话题ID
     */
    public function getTopicStats(int $topicId): array
    {
        $topic = Topic::find($topicId);
        if (! $topic) {
            return [];
        }

        $totalPosts = DB::table('post_topics')
            ->join('posts', 'post_topics.post_id', '=', 'posts.id')
            ->where('post_topics.topic_id', $topicId)
            ->where('posts.status', 1)
            ->whereNull('posts.deleted_at')
            ->count();

        $recentPosts = DB::table('post_topics')
            ->join('posts', 'post_topics.post_id', '=', 'posts.id')
            ->where('post_topics.topic_id', $topicId)
            ->where('posts.status', 1)
            ->whereNull('posts.deleted_at')
            ->where('posts.created_at', '>=', now()->subDays(7))
            ->count();

        return [
            'total_posts' => $totalPosts,
            'recent_posts' => $recentPosts,
            'follower_count' => $topic->follower_count,
        ];
    }

    /**
     * 后台管理：获取话题列表（分页）.
     *
     * @param array $params 查询参数
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getTopics(array $params = [])
    {
        $query = Topic::query();

        // 关键词搜索
        if (! empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $query->where(function ($q) use ($keyword): void {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // 状态筛选
        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }

        // 排序
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortOrder = $params['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // 分页参数处理
        $perPage = (int) ($params['per_page'] ?? 15);
        $page = $params['page'] ?? null;

        if ($page !== null) {
            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        return $query->paginate($perPage);
    }

    /**
     * 后台管理：获取话题详情.
     *
     * @param int $id 话题ID
     *
     * @return Topic
     */
    public function getTopic(int $id)
    {
        return Topic::findOrFail($id);
    }

    /**
     * 后台管理：创建话题.
     *
     * @param array $data 话题数据
     *
     * @return Topic
     */
    public function createTopic(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 如果没有指定状态，根据审核开关设置状态（与 Post/Comment 保持一致）
            if (! isset($data['status'])) {
                // 创建临时话题实例来检查审核配置
                $tempTopic = new Topic();
                $data['status'] = $tempTopic->isContentReviewEnabled()
                    ? Topic::STATUS_PENDING
                    : Topic::STATUS_PUBLISHED;
            }

            // 创建话题
            $topic = Topic::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'cover' => $data['cover'] ?? null,
                'status' => $data['status'],
            ]);

            Log::info('话题创建成功', [
                'topic_id' => $topic->id,
                'status' => $topic->status,
            ]);


            return $topic;
        });
    }

    /**
     * 后台管理：更新话题.
     *
     * @param int   $id   话题ID
     * @param array $data 更新数据
     *
     * @return Topic
     */
    public function updateTopic(int $id, array $data)
    {
        $topic = Topic::findOrFail($id);

        $topic->update([
            'name' => $data['name'] ?? $topic->name,
            'description' => $data['description'] ?? $topic->description,
            'cover' => $data['cover'] ?? $topic->cover,
            'status' => $data['status'] ?? $topic->status,
        ]);

        return $topic;
    }

    /**
     * 审核话题.
     */
    public function reviewTopic(int $id, array $data): Topic
    {
        return DB::transaction(function () use ($id, $data) {
            $topic = Topic::findOrFail($id);
            $originalStatus = (int) $topic->status;

            $status = (int) ($data['status'] ?? Topic::STATUS_PENDING);

            if (! in_array($status, [
                Topic::STATUS_PENDING,
                Topic::STATUS_ACTIVE,
                Topic::STATUS_DISABLED,
            ], true)) {
                throw new InvalidArgumentException('无效的审核状态');
            }

            if ($status === $originalStatus) {
                return $topic;
            }

            $topic->status = $status;
            $topic->save();

            TopicReviewLog::create([
                'topic_id' => $topic->id,
                'admin_id' => auth()->id(),
                'previous_status' => $originalStatus,
                'new_status' => $status,
                'remark' => $data['remark'] ?? null,
            ]);

            return $topic;
        });
    }

    /**
     * 后台管理：删除话题.
     *
     * @param int $id 话题ID
     *
     * @return null|bool
     */
    public function deleteTopic(int $id)
    {
        $topic = Topic::findOrFail($id);

        return $topic->delete();
    }

    /**
     * 检查内容审核是否启用.
     */
}
