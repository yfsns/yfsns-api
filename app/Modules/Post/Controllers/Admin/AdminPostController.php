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

namespace App\Modules\Post\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Post\Requests\Admin\AdminPostIndexRequest;
use App\Modules\Post\Requests\Admin\AdminPostStoreRequest;
use App\Modules\Post\Requests\Admin\AdminPostUpdateRequest;
use App\Modules\Post\Requests\Admin\AdminPostReviewRequest;
use App\Modules\Post\Resources\Admin\AdminPostResource;
use App\Modules\Post\Services\AdminPostService;
use Illuminate\Http\JsonResponse;

use function in_array;

/**
 * @group admin-后台管理-动态管理
 *
 * @name 后台管理-动态管理
 *
 * 提供后台动态内容的增删改查、审核等管理接口
 *
 * @authenticated
 */
class AdminPostController extends Controller
{

    protected AdminPostService $service;

    public function __construct(AdminPostService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取动态列表.
     *
     * @authenticated
     * 获取所有动态的分页列表，可按条件筛选。
     *
     * @queryParam page int 分页页码，默认1。Example: 1
     * @queryParam per_page int 每页数量，默认10。Example: 10
     * @queryParam status int 状态筛选。Example: 1
     * @queryParam type string 动态类型筛选（post=普通动态，image=图片动态，video=视频动态）。Example: post
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "list": [
     *       {
     *         "id": 1,
     *         "content": "动态内容",
     *         "status": 1
     *       }
     *     ],
     *     "page": 1,
     *     "perPage": 10,
     *     "total": 100,
     *     "lastPage": 10,
     *     "hasMore": true
     *   }
     * }
     */
    public function index(AdminPostIndexRequest $request): JsonResponse
    {
        $params = $request->validated();
        $data = $this->service->getList($params);

        // 为管理员列表加载必要的关联数据
        $data->getCollection()->load([
            'user' => function ($query) {
                $query->withEssentialFields('status');
            },
            'files:id,name,path,type,size,mime_type,storage,thumbnail,created_at,updated_at',
            'topics:id,name,description,cover,post_count,follower_count',
        ]);

        // 返回分页结果（保持原有壳结构）
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => AdminPostResource::collection($data->items()),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'prev_page_url' => $data->previousPageUrl(),
                'next_page_url' => $data->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * 获取动态详情.
     *
     * @authenticated
     * 获取指定动态的详细信息。
     *
     * @urlParam id int required 动态ID。Example: 1
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "id": 1,
     *     "content": "动态内容",
     *     "status": 1
     *   }
     * }
     */
    public function show(int $id): JsonResponse
    {
            $post = $this->service->getDetail($id);

            // 加载管理员详情页需要的关联数据
            $post->load([
                'user' => function ($query) {
                    $query->withEssentialFields('status');
                },
                'files:id,name,path,type,size,mime_type,storage,thumbnail,created_at,updated_at',
                'topics:id,name,description,cover,post_count,follower_count',
            ]);

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => new AdminPostResource($post),
            ], 200);
    }


    /**
     * 更新动态
     *
     * @authenticated
     * 修改指定动态的内容。
     *
     * @urlParam id int required 动态ID。Example: 1
     *
     * @bodyParam content string 动态内容。Example: "更新后的内容"
     * @bodyParam images array 图片列表。Example: ["url1.jpg", "url2.jpg"]
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "更新成功",
     *   "data": {
     *     "id": 1,
     *     "content": "更新后的内容",
     *     "status": 1
     *   }
     * }
     */
    public function update(AdminPostUpdateRequest $request, int $id): JsonResponse
    {
            $params = $request->validated();

            // Status 字段现在由 UpdatePostRequest 验证处理

            // 管理员权限已在 PostPolicy::before() 中处理
            $post = \App\Modules\Post\Models\Post::findOrFail($id);
            $this->authorize('update', $post);
            $updatedPost = $this->service->update($id, $params);

            // 加载管理员更新后需要的关联数据
            $updatedPost->load([
                'user' => function ($query) {
                    $query->withEssentialFields('status');
                },
                'files:id,name,path,type,size,mime_type,storage,thumbnail,created_at,updated_at',
                'topics:id,name,description,cover,post_count,follower_count',
            ]);

            return response()->json([
                'code' => 200,
                'message' => '更新成功',
                'data' => new AdminPostResource($updatedPost),
            ], 200);
    }

    /**
     * 删除动态
     *
     * @authenticated
     * 删除指定动态。
     *
     * @urlParam id int required 动态ID。Example: 1
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "删除成功",
     *   "data": null
     * }
     */
    public function destroy(int $id): JsonResponse
    {
            $post = \App\Modules\Post\Models\Post::findOrFail($id);
            // 管理员权限已在 PostPolicy::before() 中处理
            $this->authorize('delete', $post);
            $this->service->delete($id);

            return response()->json([
                'code' => 200,
                'message' => '删除成功',
                'data' => null,
            ], 200);
    }

    /**
     * 审核动态
     *
     * @authenticated
     * 审核指定动态，修改其状态。
     *
     * @urlParam id int required 动态ID。Example: 1
     *
     * @bodyParam status int required 审核状态（1=通过，0=拒绝）。Example: 1
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "审核成功",
     *   "data": {
     *     "id": 1,
     *     "status": 1
     *   }
     * }
     */
    public function review(AdminPostReviewRequest $request, int $id): JsonResponse
    {
            $post = \App\Modules\Post\Models\Post::findOrFail($id);
            // 管理员权限已在 PostPolicy::before() 中处理
            $this->authorize('review', $post);
            $params = $request->validated();
            $reviewedPost = $this->service->reviewPost($id, $params);

            // 加载管理员审核后需要的关联数据
            $reviewedPost->load([
                'user' => function ($query) {
                    $query->withEssentialFields('status');
                },
                'files:id,name,path,type,size,mime_type,storage,thumbnail,created_at,updated_at',
                'topics:id,name,description,cover,post_count,follower_count',
            ]);

            return response()->json([
                'code' => 200,
                'message' => '审核成功',
                'data' => new AdminPostResource($reviewedPost),
            ], 200);
    }
}
