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

namespace App\Modules\Search\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Search\Requests\GetHotSearchesRequest;
use App\Modules\Search\Requests\GetSuggestionsRequest;
use App\Modules\Search\Requests\GlobalSearchRequest;
use App\Modules\Search\Requests\SearchRequest;
use App\Modules\Search\Resources\SearchResultResource;
use App\Modules\Search\Services\SearchService;

use function count;

use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * 全局搜索.
     */
    public function globalSearch(GlobalSearchRequest $request): JsonResponse
    {
        $data = $request->validated();

        $query = $data['q'];
        $type = $data['type'] ?? 'all';
        $limit = $data['limit'] ?? 20;
        $filters = $data['filters'] ?? [];

        if ($type === 'all') {
            $rawResults = $this->searchService->globalSearch($query, $filters, $limit);

            // 将每个分类的结果转换为Resource格式
            $results = [];
            foreach ($rawResults as $category => $items) {
                $results[$category] = SearchResultResource::collection($items)->toArray($request);
            }
        } else {
            $method = 'search' . ucfirst($type);
            if (method_exists($this->searchService, $method)) {
                $rawResults = $this->searchService->$method($query, $filters, $limit);
                $results = [$type => SearchResultResource::collection($rawResults)->toArray($request)];
            } else {
                return response()->json([
                    'code' => 400,
                    'message' => '不支持的搜索类型',
                    'data' => null,
                ], 400);
            }
        }

        // 计算总数
        $total = 0;
        foreach ($results as $items) {
            $total += is_countable($items) ? count($items) : 0;
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'results' => $results,
                'query' => $query,
                'type' => $type,
                'total' => $total,
            ],
        ], 200);
    }

    /**
     * 搜索动态
     */
    public function searchPosts(SearchRequest $request): JsonResponse
    {
        $data = $request->validated();

        $query = $data['q'];
        $limit = $data['limit'] ?? 20;
        $filters = $data['filters'] ?? [];

        $posts = $this->searchService->searchPosts($query, $filters, $limit);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => SearchResultResource::collection($posts),
                'query' => $query,
                'total' => $posts->count(),
            ],
        ], 200);
    }

    /**
     * 搜索用户
     * 优化：返回完整的User对象，包含统计字段.
     */
    public function searchUsers(SearchRequest $request): JsonResponse
    {
        $data = $request->validated();

        $query = $data['q'];
        $limit = $data['limit'] ?? 20;
        $filters = $data['filters'] ?? [];

        $users = $this->searchService->searchUsers($query, $filters, $limit);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => \App\Modules\User\Resources\UserResource::collection($users),
                'query' => $query,
                'total' => $users->count(),
            ],
        ], 200);
    }

    /**
     * 搜索评论.
     */
    public function searchComments(SearchRequest $request): JsonResponse
    {
        $data = $request->validated();

        $query = $data['q'];
        $limit = $data['limit'] ?? 20;
        $filters = $data['filters'] ?? [];

        $comments = $this->searchService->searchComments($query, $filters, $limit);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => SearchResultResource::collection($comments),
                'query' => $query,
                'total' => $comments->count(),
            ],
        ], 200);
    }

    /**
     * 搜索话题
     * 优化：返回完整的Topic对象，包含统计字段（postCount、followerCount）.
     */
    public function searchTopics(SearchRequest $request): JsonResponse
    {
        $data = $request->validated();

        $query = $data['q'];
        $limit = $data['limit'] ?? 20;
        $filters = $data['filters'] ?? [];

        $topics = $this->searchService->searchTopics($query, $filters, $limit);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => \App\Modules\Topic\Resources\TopicResource::collection($topics),
                'query' => $query,
                'total' => $topics->count(),
            ],
        ], 200);
    }


    /**
     * 获取搜索建议.
     */
    public function getSuggestions(GetSuggestionsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $query = $data['q'];
        $limit = $data['limit'] ?? 10;

        $suggestions = $this->searchService->getSuggestions($query, $limit);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'suggestions' => $suggestions,
                'query' => $query,
            ],
        ], 200);
    }

    /**
     * 获取热门搜索词.
     */
    public function getHotSearches(GetHotSearchesRequest $request): JsonResponse
    {
        $data = $request->validated();

        $limit = $data['limit'] ?? 20;
        $hotSearches = $this->searchService->getHotSearches($limit);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => $hotSearches,
                'total' => $hotSearches->count(),
            ],
        ], 200);
    }
}
