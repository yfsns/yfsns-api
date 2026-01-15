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

namespace App\Modules\Comment\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Comment\Requests\Admin\AuditCommentRequest;
use App\Modules\Comment\Requests\Admin\BatchAuditCommentsRequest;
use App\Modules\Comment\Requests\Admin\BatchDestroyCommentsRequest;
use App\Modules\Comment\Requests\Admin\GetCommentsRequest;
use App\Modules\Comment\Requests\Admin\UpdateCommentStatusRequest;
use App\Modules\Comment\Resources\CommentResource;
use App\Modules\Comment\Services\AdminCommentService;
use Illuminate\Http\JsonResponse;

/**
 * @group admin-后台管理-评论管理
 *
 * @name 后台管理-评论管理
 */
class CommentController extends Controller
{

    protected $service;

    public function __construct(AdminCommentService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取评论列表.
     *
     * @authenticated
     *
     * 获取评论列表，支持分页和筛选
     *
     * @queryParam page int 页码. Example: 1
     * @queryParam per_page int 每页数量. Example: 10
     * @queryParam status int 评论状态(0:待审核,1:已发布,2:已拒绝). Example: 1
     * @queryParam keyword string 可选,搜索关键词. Example: 评论内容
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "获取成功",
     *     "data": {
     *         "current_page": 1,
     *         "data": [
     *             {
     *                 "id": 1,
     *                 "content": "评论内容",
     *                 "user_id": 1,
     *                 "user": {
     *                     "id": 1,
     *                     "nickname": "用户昵称",
     *                     "avatar": "头像地址"
     *                 },
     *                 "status": 1,
     *                 "created_at": "2024-03-20 10:00:00"
     *             }
     *         ],
     *         "total": 100,
     *         "per_page": 15
     *     }
     * }
     */
    public function index(GetCommentsRequest $request): JsonResponse
    {
        $pagination = $this->service->getList($request->validated());

        // 返回传统分页结果（保持壳结构）
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => CommentResource::collection($pagination->items()),
                'current_page' => $pagination->currentPage(),
                'last_page' => $pagination->lastPage(),
                'per_page' => $pagination->perPage(),
                'total' => $pagination->total(),
                'next_page_url' => $pagination->nextPageUrl(),
                'prev_page_url' => $pagination->previousPageUrl(),
                'from' => $pagination->firstItem(),
                'to' => $pagination->lastItem(),
            ],
        ], 200);
    }

    /**
     * 更新评论状态
     *
     * @authenticated
     *
     * 更新评论的审核状态
     *
     * @urlParam id int required 评论ID. Example: 1
     *
     * @bodyParam status int required 状态(1:通过,2:拒绝). Example: 1
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "更新成功",
     *     "data": {
     *         "id": 1,
     *         "status": 1
     *     }
     * }
     * @response 404 {
     *     "code": 404,
     *     "message": "评论不存在"
     * }
     */
    public function updateStatus(UpdateCommentStatusRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $comment = $this->service->updateStatus($id, $validated['status']);

        return response()->json([
            'code' => 200,
            'message' => '更新成功',
            'data' => new CommentResource($comment),
        ], 200);
    }

    /**
     * 审核评论.
     *
     * @authenticated
     *
     * 审核指定评论
     *
     * @urlParam id int required 评论ID. Example: 1
     *
     * @bodyParam status int required 审核状态(1:通过,2:拒绝). Example: 1
     * @bodyParam reason string 拒绝原因（当状态为拒绝时）. Example: 违规内容
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "审核成功",
     *     "data": {
     *         "id": 1,
     *         "status": 1,
     *         "audit_reason": null,
     *         "audited_at": "2024-03-20 10:00:00"
     *     }
     * }
     * @response 404 {
     *     "code": 404,
     *     "message": "评论不存在"
     * }
     */
    public function audit(AuditCommentRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        $comment = $this->service->audit($id, $data['status'], $data['reason'] ?? '');

        return response()->json([
            'code' => 200,
            'message' => '审核成功',
            'data' => new CommentResource($comment),
        ], 200);
    }

    /**
     * 删除评论（根据ID）.
     *
     * @authenticated
     *
     * 删除指定的评论
     *
     * @urlParam id int required 评论ID. Example: 1
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "删除成功"
     * }
     * @response 404 {
     *     "code": 404,
     *     "message": "评论不存在"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteById($id);

        return response()->json([
            'code' => 200,
            'message' => '删除成功',
            'data' => null,
        ], 200);
    }

    /**
     * 批量删除评论.
     *
     * @authenticated
     *
     * 批量删除多个评论
     *
     * @bodyParam ids array required 评论ID数组. Example: [1,2,3]
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "删除成功"
     * }
     */
    public function batchDestroy(BatchDestroyCommentsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->service->batchDelete($data['ids']);

        return response()->json([
            'code' => 200,
            'message' => '删除成功',
            'data' => null,
        ], 200);
    }

    /**
     * 获取评论详情.
     *
     * @authenticated
     *
     * 获取指定评论的详细信息
     *
     * @urlParam id int required 评论ID. Example: 1
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "获取成功",
     *     "data": {
     *         "id": 1,
     *         "content": "评论内容",
     *         "user_id": 1,
     *         "user": {
     *             "id": 1,
     *             "nickname": "用户昵称",
     *             "avatar": "头像地址"
     *         },
     *         "status": 1,
     *         "created_at": "2024-03-20 10:00:00",
     *         "target": {
     *             "id": 1,
     *             "content": "被评论的内容"
     *         }
     *     }
     * }
     * @response 404 {
     *     "code": 404,
     *     "message": "评论不存在"
     * }
     */
    public function show(int $id): JsonResponse
    {
        $data = $this->service->getDetail($id);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $data,
        ], 200);
    }

    /**
     * 批量审核评论.
     *
     * @authenticated
     *
     * 批量审核多个评论
     *
     * @bodyParam ids array required 评论ID数组. Example: [1,2,3]
     * @bodyParam status int required 审核状态(1:通过,2:拒绝). Example: 1
     * @bodyParam reason string 拒绝原因（当状态为拒绝时）. Example: 违规内容
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "批量审核成功",
     *     "data": {
     *         "success_count": 3,
     *         "failed_count": 0
     *     }
     * }
     */
    public function batchAudit(BatchAuditCommentsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->service->batchAudit($data['ids'], $data['status'], $data['reason'] ?? '');

        return response()->json([
            'code' => 200,
            'message' => '批量审核成功',
            'data' => $result,
        ], 200);
    }

    /**
     * 获取评论审核统计数据.
     *
     * @authenticated
     *
     * 获取评论审核相关的统计数据
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "获取成功",
     *     "data": {
     *         "total": 100,
     *         "pending": 20,
     *         "approved": 70,
     *         "rejected": 10,
     *         "today_count": 5,
     *         "week_count": 25
     *     }
     * }
     */
    public function statistics(): JsonResponse
    {
        $data = $this->service->getStatistics();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $data,
        ], 200);
    }
}
