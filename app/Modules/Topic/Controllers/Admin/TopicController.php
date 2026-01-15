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

namespace App\Modules\Topic\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Topic\Requests\Admin\ReviewTopicRequest;
use App\Modules\Topic\Resources\TopicResource;
use App\Modules\Topic\Services\TopicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group admin-后台管理-话题管理
 *
 * 后台话题管理相关的API接口，包括话题的创建、编辑、删除、列表等功能
 *
 * @authenticated
 */
class TopicController extends Controller
{

    protected $service;

    public function __construct(TopicService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取话题列表.
     *
     * 获取所有话题的列表，支持分页和关键词搜索
     *
     * @queryParam per_page int 每页显示数量，默认10。Example: 10
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "获取成功",
     *     "data": {
     *         "list": [
     *             {
     *                 "id": 1,
     *                 "name": "热门话题",
     *                 "description": "这是一个热门话题",
     *                 "cover": null,
     *                 "post_count": 0,
     *                 "follower_count": 0,
     *                 "status": 1,
     *                 "created_at": "2024-03-20T10:00:00.000000Z",
     *                 "updated_at": "2024-03-20T10:00:00.000000Z",
     *                 "deleted_at": null
     *             }
     *         ],
     *         "page": 1,
     *         "perPage": 15,
     *         "total": 1,
     *         "lastPage": 1,
     *         "hasMore": false
     *     }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $topics = $this->service->getTopics($request->all());

        // 返回Laravel原生分页格式
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => TopicResource::collection($topics->items()),
                'current_page' => $topics->currentPage(),
                'last_page' => $topics->lastPage(),
                'per_page' => $topics->perPage(),
                'total' => $topics->total(),
                'from' => $topics->firstItem(),
                'to' => $topics->lastItem(),
                'prev_page_url' => $topics->previousPageUrl(),
                'next_page_url' => $topics->nextPageUrl(),
            ]
        ]);
    }

    /**
     * 创建话题.
     *
     * 创建一个新的话题
     *
     * @bodyParam title string required 话题标题. Example: 热门话题
     * @bodyParam description string required 话题描述. Example: 这是一个热门话题的描述
     * @bodyParam status string required 话题状态：active/inactive. Example: active
     *
     * @response 201 {
     *     "code": 201,
     *     "message": "创建成功",
     *     "data": {
     *         "id": 1,
     *         "title": "热门话题",
     *         "description": "这是一个热门话题的描述",
     *         "status": "active",
     *         "created_at": "2024-03-20T10:00:00.000000Z",
     *         "updated_at": "2024-03-20T10:00:00.000000Z"
     *     }
     * }
     * @response 422 {
     *     "code": 422,
     *     "message": "验证失败",
     *     "data": {
     *         "title": ["标题不能为空"],
     *         "description": ["描述不能为空"],
     *         "status": ["状态值无效"]
     *     }
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $topic = $this->service->createTopic($request->all());

        return response()->json([
            'code' => 201,
            'message' => '创建成功',
            'data' => new TopicResource($topic),
        ], 201);
    }

    /**
     * 获取话题详情.
     *
     * 获取指定话题的详细信息
     *
     * @urlParam id integer required 话题ID. Example: 1
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "获取成功",
     *     "data": {
     *         "id": 1,
     *         "title": "热门话题",
     *         "description": "这是一个热门话题的描述",
     *         "status": "active",
     *         "created_at": "2024-03-20T10:00:00.000000Z",
     *         "updated_at": "2024-03-20T10:00:00.000000Z"
     *     }
     * }
     * @response 404 {
     *     "code": 404,
     *     "message": "话题不存在",
     *     "data": null
     * }
     */
    public function show(int $id): JsonResponse
    {
        $topic = $this->service->getTopic($id);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => new TopicResource($topic),
        ], 200);
    }

    /**
     * 更新话题.
     *
     * 更新指定话题的信息
     *
     * @urlParam id integer required 话题ID. Example: 1
     *
     * @bodyParam title string 话题标题. Example: 更新后的标题
     * @bodyParam description string 话题描述. Example: 更新后的描述
     * @bodyParam status string 话题状态：active/inactive. Example: active
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "更新成功",
     *     "data": {
     *         "id": 1,
     *         "title": "更新后的标题",
     *         "description": "更新后的描述",
     *         "status": "active",
     *         "created_at": "2024-03-20T10:00:00.000000Z",
     *         "updated_at": "2024-03-20T10:00:00.000000Z"
     *     }
     * }
     * @response 404 {
     *     "code": 404,
     *     "message": "话题不存在",
     *     "data": null
     * }
     * @response 422 {
     *     "code": 422,
     *     "message": "验证失败",
     *     "data": {
     *         "title": ["标题不能为空"],
     *         "description": ["描述不能为空"],
     *         "status": ["状态值无效"]
     *     }
     * }
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $topic = $this->service->updateTopic($id, $request->all());

        return response()->json([
            'code' => 200,
            'message' => '更新成功',
            'data' => new TopicResource($topic),
        ], 200);
    }

    /**
     * 审核话题.
     *
     * @authenticated
     *
     * @urlParam id integer required 话题ID
     *
     * @bodyParam status integer required 审核状态（0=待审核，1=启用，2=禁用）
     */
    public function review(ReviewTopicRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $topic = $this->service->reviewTopic($id, $data);

        return response()->json([
            'code' => 200,
            'message' => '审核成功',
            'data' => new TopicResource($topic),
        ], 200);
    }

    /**
     * 删除话题.
     *
     * 删除指定的话题
     *
     * @urlParam id integer required 话题ID. Example: 1
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "删除成功",
     *     "data": null
     * }
     * @response 404 {
     *     "code": 404,
     *     "message": "话题不存在",
     *     "data": null
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteTopic($id);

        return response()->json([
            'code' => 200,
            'message' => '删除成功',
            'data' => null,
        ], 200);
    }
}
