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

namespace App\Modules\Like\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Like\Requests\CheckLikeRequest;
use App\Modules\Like\Requests\GetLikeCountRequest;
use App\Modules\Like\Requests\GetLikesRequest;
use App\Modules\Like\Requests\ToggleLikeRequest;
use App\Modules\Like\Resources\LikeResource;
use App\Modules\Like\Services\LikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @group 点赞模块
 *
 * @name 点赞模块
 */
class LikeController extends Controller
{

    protected $likeService;

    public function __construct(LikeService $likeService)
    {
        $this->likeService = $likeService;
    }

    /**
     * 切换点赞状态（推荐使用）.
     *
     * @authenticated
     *
     * @bodyParam model_type string required 模型类型，可选值：post, comment, topic, user, forum_thread, forum_threadreply, article
     * @bodyParam type string 点赞类型，可选值：default, love, like 等
     *
     * @response 200 {
     *   "message": "操作成功",
     *   "data": {
     *     "isLiked": true,
     *     "likeCount": 5,
     *     "action": "liked"
     *   }
     * }
     */
    public function toggle(ToggleLikeRequest $request, $id): JsonResponse
    {
        $data = $request->validated();

        // 记录请求参数，方便调试
        Log::info('点赞请求参数', [
            'model_type' => $data['model_type'] ?? null,
            'id' => $id,
            'all_data' => $data,
            'all_input' => $request->all(),
        ]);

        $model = $this->likeService->getModelByType($data['model_type'], $id);
        $wasLiked = $this->likeService->isLiked($model);

        if ($wasLiked) {
            // 如果已经点赞，则取消点赞
            $result = $this->likeService->unlike($model);
            $action = 'unliked';
            $message = '取消点赞成功';
        } else {
            // 如果没有点赞，则点赞
            $result = $this->likeService->like($model, $data['type']);
            $action = 'liked';
            $message = '点赞成功';
        }

        // 强制刷新模型实例，确保获取最新的点赞数
        $model->refresh();

        $responseData = [
            'isLiked' => ! $wasLiked,
            'likeCount' => $this->likeService->getLikeCount($model),
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
     * 获取点赞列表.
     *
     * @authenticated
     *
     * @queryParam type string 点赞类型过滤
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
     *         "likeableId": 1,
     *         "likeableType": "App\\Modules\\Comment\\Models\\Comment",
     *         "type": "default",
     *         "createdAt": "2024-03-20 10:00:00"
     *       }
     *     ],
     *     "total": 1,
     *     "perPage": 10
     *   }
     * }
     */
    public function list(GetLikesRequest $request): JsonResponse
    {
        $data = $request->validated();

        $modelClass = $data['model_type'] ? $this->likeService->getModelClass($data['model_type']) : null;
        $likes = $this->likeService->getUserLikes(
            $data['type'] ?? null,
            $modelClass,
            $data['page'] ?? 1,
            $data['per_page'] ?? 10
        );

        // 返回分页结果（保持壳结构）
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => LikeResource::collection($likes->items()),
                'current_page' => $likes->currentPage(),
                'last_page' => $likes->lastPage(),
                'per_page' => $likes->perPage(),
                'total' => $likes->total(),
                'from' => $likes->firstItem(),
                'to' => $likes->lastItem(),
                'prev_page_url' => $likes->previousPageUrl(),
                'next_page_url' => $likes->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * 检查是否已点赞.
     *
     * @authenticated
     *
     * @queryParam model_type string required 模型类型，可选值：post, comment, topic, user, forum_thread, forum_post
     *
     * @response 200 {
     *   "message": "操作成功",
     *   "data": {
     *     "isLiked": true
     *   }
     * }
     */
    public function check(CheckLikeRequest $request, $id): JsonResponse
    {
        $data = $request->validated();

        $model = $this->likeService->getModelByType($data['model_type'], $id);
        $isLiked = $this->likeService->isLiked($model);

        return response()->json([
            'code' => 200,
            'message' => '操作成功',
            'data' => ['isLiked' => $isLiked],
        ], 200);
    }

    /**
     * 获取点赞数量.
     *
     * @authenticated
     *
     * @queryParam model_type string required 模型类型，可选值：post, comment, topic, user, forum_thread, forum_post
     *
     * @response 200 {
     *   "message": "操作成功",
     *   "data": {
     *     "count": 10
     *   }
     *   }
     */
    public function count(GetLikeCountRequest $request, $id): JsonResponse
    {
        $data = $request->validated();

        $model = $this->likeService->getModelByType($data['model_type'], $id);
        $count = $this->likeService->getLikeCount($model);

        return response()->json([
            'code' => 200,
            'message' => '操作成功',
            'data' => ['likeCount' => $count],
        ], 200);
    }
}
