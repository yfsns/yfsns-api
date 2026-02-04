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

namespace App\Modules\Topic\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Topic\Requests\GetTopicPostsRequest;
use App\Modules\Topic\Requests\GetTopicsRequest;
use App\Modules\Topic\Requests\GetTopicTrendsRequest;
use App\Modules\Topic\Requests\SearchTopicsRequest;
use App\Modules\Topic\Requests\StoreTopicRequest;
use App\Modules\Topic\Services\TopicService;

use function count;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{

    protected $topicService;

    public function __construct(TopicService $topicService)
    {
        $this->topicService = $topicService;
    }

    /**
     * 获取话题列表.
     *
     * @param Request $request
     */
    public function index(GetTopicsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $limit = min((int) ($data['limit'] ?? 20), 100);
        $days = (int) ($data['days'] ?? 0);  // 默认0表示不限制时间

        $topics = $this->topicService->getHotTopics($limit, $days);

        return response()->json([
            'code' => 200,
            'message' => '获取话题列表成功',
            'data' => [
                'list' => $topics,
                'total' => count($topics),
            ],
        ], 200);
    }

    /**
     * 获取热门话题（推荐使用recommend接口）.
     *
     * @param Request $request
     */
    public function getHotTopics(GetTopicsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $limit = min((int) ($data['limit'] ?? 20), 100);
        $days = (int) ($data['days'] ?? 0);  // 默认0表示不限制时间

        $topics = $this->topicService->getHotTopics($limit, $days);

        return response()->json([
            'code' => 200,
            'message' => '获取热门话题成功',
            'data' => [
                'list' => $topics,
                'total' => count($topics),
            ],
        ], 200);
    }

    /**
     * 获取推荐话题列表.
     *
     * 获取最新的5个话题，用于首页展示
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取推荐话题成功",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "话题名称",
     *       "description": "话题描述",
     *       "cover": "封面图片",
     *       "postCount": 100,
     *       "followerCount": 50
     *     }
     *   ]
     * }
     */
    public function recommend(): JsonResponse
    {
        // 固定返回5条推荐话题
        $topics = $this->topicService->getRecommendedTopics(5);

        return response()->json([
            'code' => 200,
            'message' => '获取推荐话题成功',
            'data' => $topics,
        ], 200);
    }

    /**
     * 获取话题详情（使用ID）.
     */
    public function getTopicDetail(int $id): JsonResponse
    {
        $topic = $this->topicService->getTopicDetailById($id);

        if (! $topic) {
            return response()->json([
                'code' => 404,
                'message' => '话题不存在',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => '获取话题详情成功',
            'data' => $topic,
        ], 200);
    }

    /**
     * 获取话题趋势（使用ID）.
     *
     * @param Request $request
     */
    public function getTopicTrends(GetTopicTrendsRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $days = min((int) ($data['days'] ?? 7), 30);

        $trends = $this->topicService->getTopicTrendsById($id, $days);

        return response()->json([
            'code' => 200,
            'message' => '获取话题趋势成功',
            'data' => $trends,
        ], 200);
    }

    /**
     * 搜索话题.
     *
     * @param Request $request
     */
    public function searchTopics(SearchTopicsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $keyword = $data['keyword'];
        $limit = min((int) ($data['limit'] ?? 10), 50);

        $topics = $this->topicService->searchTopics($keyword, $limit);

        return response()->json([
            'code' => 200,
            'message' => '搜索话题成功',
            'data' => [
                'topics' => $topics,
                'keyword' => $keyword,
                'total' => count($topics),
            ],
        ], 200);
    }

    /**
     * 获取话题下的动态列表（使用ID）.
     *
     * @param Request $request
     */
    public function getTopicPosts(GetTopicPostsRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $perPage = min((int) ($data['per_page'] ?? 20), 100);

        $posts = $this->topicService->getTopicPostsById($id, $perPage);

        // 返回Laravel原生分页格式
        return response()->json([
            'code' => 200,
            'message' => '获取话题动态成功',
            'data' => [
                'data' => $posts->items(),
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'from' => $posts->firstItem(),
                'to' => $posts->lastItem(),
                'prev_page_url' => $posts->previousPageUrl(),
                'next_page_url' => $posts->nextPageUrl(),
            ]
        ]);
    }

    /**
     * 获取话题统计信息.
     */
    public function getTopicStats(int $topicId): JsonResponse
    {
        $stats = $this->topicService->getTopicStats($topicId);

        if (empty($stats)) {
            return response()->json([
                'code' => 404,
                'message' => '话题不存在',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => '获取话题统计成功',
            'data' => $stats,
        ], 200);
    }

    /**
     * 创建话题（前台用户可创建）.
     *
     * @authenticated
     *
     * @param Request $request
     */
    public function store(StoreTopicRequest $request): JsonResponse
    {
        $data = $request->validated();

        $topic = $this->topicService->createTopic($data);

        return response()->json([
            'code' => 201,
            'message' => '话题创建成功',
            'data' => [
                'id' => $topic->id,
                'name' => $topic->name,
                'description' => $topic->description,
                'cover' => $topic->cover,
                'postCount' => $topic->post_count,
                'followerCount' => $topic->follower_count,
            ],
        ], 201);
    }
}
