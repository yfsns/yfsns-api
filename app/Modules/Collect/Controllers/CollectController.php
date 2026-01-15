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

namespace App\Modules\Collect\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Collect\Requests\CheckCollectRequest;
use App\Modules\Collect\Requests\GetCollectCountRequest;
use App\Modules\Collect\Requests\GetCollectsRequest;
use App\Modules\Collect\Requests\ToggleCollectRequest;
use App\Modules\Collect\Resources\CollectResource;
use App\Modules\Collect\Services\CollectService;
use Illuminate\Http\JsonResponse;

/**
 * @group 收藏模块
 *
 * @name 收藏模块
 */
class CollectController extends Controller
{

    protected $collectService;

    public function __construct(CollectService $collectService)
    {
        $this->collectService = $collectService;
    }

    /**
     * 切换收藏状态（推荐使用）.
     *
     * @authenticated
     *
     * @bodyParam model_type string required 模型类型，可选值：post, comment, topic, user, forum_thread, forum_threadreply, article
     * @bodyParam type string 收藏类型，可选值：default, favorite 等
     * @bodyParam remark string 收藏备注
     *
     * @response 200 {
     *   "message": "操作成功",
     *   "data": {
     *     "isCollected": true,
     *     "collectCount": 5,
     *     "action": "collected"
     *   }
     * }
     */
    public function toggle(ToggleCollectRequest $request, $id): JsonResponse
    {
        $data = $request->validated();

        $model = $this->collectService->getModelByType($data['model_type'], $id);
        $wasCollected = $this->collectService->isCollected($model);

        if ($wasCollected) {
            // 如果已经收藏，则取消收藏
            $result = $this->collectService->uncollect($model);
            $action = 'uncollected';
            $message = '取消收藏成功';
        } else {
            // 如果没有收藏，则收藏
            $result = $this->collectService->collect(
                $model,
                $data['type'],
                $data['remark'] ?? null
            );
            $action = 'collected';
            $message = '收藏成功';
        }

        // 强制刷新模型实例，确保获取最新的收藏数
        $model->refresh();

        $responseData = [
            'isCollected' => ! $wasCollected,
            'collectCount' => $this->collectService->getCollectCount($model),
            'action' => $action,
            'message' => $message,
        ];

        return response()->json([
            'code' => 200,
            'message' => $message,
            'data' => $responseData,
        ], 200);
    }

    /**
     * 获取收藏列表.
     *
     * @authenticated
     *
     * @queryParam type string 收藏类型过滤
     * @queryParam model_type string 模型类型过滤，可选值：post, comment, topic, user, forum_thread, forum_threadreply, article
     * @queryParam page integer 页码，默认1
     * @queryParam per_page integer 每页数量，默认10，最大100
     *
     * @response 200 {
     *   "message": "操作成功",
     *   "data": {
     *     "currentPage": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "userId": 1,
     *         "collectableId": 1,
     *         "collectableType": "App\\Modules\\Post\\Models\\Post",
     *         "type": "default",
     *         "remark": null,
     *         "createdAt": "2024-03-20 10:00:00"
     *       }
     *     ],
     *     "total": 1,
     *     "perPage": 10
     *   }
     * }
     */
    public function list(GetCollectsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $modelType = $data['model_type'] ?? null;
        $modelClass = $modelType ? $this->collectService->getModelClass($modelType) : null;
        $collects = $this->collectService->getUserCollects(
            $data['type'] ?? null,
            $modelType, // 传递类型标识符，而不是完整的类名
            $data['page'] ?? 1,
            $data['per_page'] ?? 10
        );

        // 返回分页结果（保持原有壳结构）
        return response()->json([
            'code' => 200,
            'message' => '获取收藏列表成功',
            'data' => [
                'data' => CollectResource::collection($collects->items()),
                'current_page' => $collects->currentPage(),
                'last_page' => $collects->lastPage(),
                'per_page' => $collects->perPage(),
                'total' => $collects->total(),
                'from' => $collects->firstItem(),
                'to' => $collects->lastItem(),
                'prev_page_url' => $collects->previousPageUrl(),
                'next_page_url' => $collects->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * 检查是否已收藏.
     *
     * @authenticated
     *
     * @queryParam model_type string required 模型类型，可选值：post, comment, topic, user, forum_thread, forum_post
     *
     * @response 200 {
     *   "message": "操作成功",
     *   "data": {
     *     "isCollected": true
     *   }
     * }
     */
    public function check(CheckCollectRequest $request, $id): JsonResponse
    {
        $data = $request->validated();

        $model = $this->collectService->getModelByType($data['model_type'], $id);
        $isCollected = $this->collectService->isCollected($model);

        return response()->json([
            'code' => 200,
            'message' => '操作成功',
            'data' => ['isCollected' => $isCollected],
        ], 200);
    }

    /**
     * 获取收藏数量.
     *
     * @authenticated
     *
     * @queryParam model_type string required 模型类型，可选值：post, comment, topic, user, forum_thread, forum_post
     *
     * @response 200 {
     *   "message": "操作成功",
     *   "data": {
     *     "collectCount": 10
     *   }
     * }
     */
    public function count(GetCollectCountRequest $request, $id): JsonResponse
    {
        $data = $request->validated();

        $model = $this->collectService->getModelByType($data['model_type'], $id);
        $count = $this->collectService->getCollectCount($model);

        return response()->json([
            'code' => 200,
            'message' => '操作成功',
            'data' => ['collectCount' => $count],
        ], 200);
    }
}
