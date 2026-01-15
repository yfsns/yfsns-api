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

namespace App\Modules\User\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\User\Requests\Admin\BatchDestroyUsersRequest;
use App\Modules\User\Requests\Admin\GetUsersRequest;
use App\Modules\User\Requests\Admin\ResetPasswordRequest;
use App\Modules\User\Requests\Admin\StoreUserRequest;
use App\Modules\User\Requests\Admin\UpdateUserRequest;
use App\Modules\User\Requests\Admin\UpdateUserStatusRequest;
use App\Modules\User\Resources\AdminUserResource;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Services\AdminUserService;
use Illuminate\Http\JsonResponse;

/**
 * @group admin-后台管理-用户管理
 *
 * @name 用户管理
 */
class UserController extends Controller
{

    protected $service;

    public function __construct(AdminUserService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取用户列表.
     *
     * @authenticated
     *
     * @queryParam keyword string 搜索关键词
     * @queryParam status int 状态
     * @queryParam sort_field string 排序字段
     * @queryParam sort_order string 排序方式（asc/desc）
     * @queryParam per_page int 每页数量（支持：10、20、50、100）
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "username": "user1",
     *         "nickname": "用户1",
     *         "email": "user1@example.com",
     *         "status": 1,
     *         "created_at": "2024-01-01 00:00:00"
     *       }
     *     ],
     *     "total": 100
     *   }
     * }
     */
    public function index(GetUsersRequest $request): JsonResponse
    {
        $params = $request->validated();

        // 使用AdminUserResource标准化参数
        $normalizedParams = AdminUserResource::normalizeQueryParams($params);

        // 添加分页参数
        $perPage = min((int) ($normalizedParams['per_page'] ?? 15), 100);
        $allowedOptions = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedOptions)) {
            $perPage = 15;
        }
        $pagination = [
            'page' => (int) ($normalizedParams['page'] ?? 1),
            'per_page' => $perPage,
        ];

        $normalizedParams = array_merge($normalizedParams, $pagination);
        $users = $this->service->getList($normalizedParams);

        // 返回Laravel原生分页格式
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => AdminUserResource::collection($users->items()),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
                'prev_page_url' => $users->previousPageUrl(),
                'next_page_url' => $users->nextPageUrl(),
            ]
        ]);
    }

    /**
     * 获取用户详情.
     *
     * @authenticated
     *
     * @urlParam id int required 用户ID
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "id": 1,
     *     "username": "user1",
     *     "nickname": "用户1",
     *     "email": "user1@example.com",
     *     "status": 1,
     *     "roles": [
     *       {
     *         "id": 1,
     *         "name": "管理员"
     *       }
     *     ],
     *     "created_at": "2024-01-01 00:00:00"
     *   }
     * }
     */
    public function show(int $id): JsonResponse
    {
            $user = $this->service->getDetail($id);

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => new UserResource($user),
            ], 200);
    }

    /**
     * 创建用户.
     *
     * @bodyParam username string required 用户名
     * @bodyParam password string required 密码
     * @bodyParam nickname string 昵称
     * @bodyParam email string required 邮箱
     * @bodyParam status int 状态
     * @bodyParam role_id int 角色ID（替代原 group_id）
     *
     * @response {
     *   "code": 200,
     *   "message": "创建成功",
     *   "data": {
     *     "id": 1,
     *     "username": "user1",
     *     "nickname": "用户1",
     *     "email": "user1@example.com",
     *     "status": 1
     *   }
     * }
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
            $data = $request->validated();
            $user = $this->service->create($data);

            return response()->json([
                'code' => 201,
                'message' => '用户创建成功',
                'data' => new UserResource($user->load('role')),
            ], 201);
    }

    /**
     * 更新用户.
     *
     * @authenticated
     *
     * @urlParam id int required 用户ID
     *
     * @bodyParam nickname string 昵称
     * @bodyParam email string 邮箱
     * @bodyParam role_id int 角色ID
     *
     * @response {
     *   "code": 200,
     *   "message": "更新成功",
     *   "data": {
     *     "id": 1,
     *     "nickname": "新昵称",
     *     "email": "new@example.com",
     *     "status": 1
     *   }
     * }
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
            $data = $request->validated();
            $user = $this->service->update($id, $data);

            return response()->json([
                'code' => 200,
                'message' => '用户更新成功',
                'data' => new UserResource($user->load('role')),
            ], 200);
    }

    /**
     * 删除用户.
     *
     * @authenticated
     *
     * @urlParam id int required 用户ID
     *
     * @response {
     *   "code": 200,
     *   "message": "删除成功"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
            $this->service->delete($id);

            return response()->json([
                'code' => 200,
                'message' => '用户删除成功',
                'data' => null,
            ], 200);
    }

    /**
     * 批量删除用户.
     *
     * @authenticated
     *
     * @bodyParam ids array required 用户ID列表
     *
     * @response {
     *   "code": 200,
     *   "message": "删除成功"
     * }
     */
    public function batchDestroy(BatchDestroyUsersRequest $request): JsonResponse
    {
            $data = $request->validated();
            $this->service->batchDelete($data['ids']);

            return response()->json([
                'code' => 200,
                'message' => '用户批量删除成功',
                'data' => null,
            ], 200);
    }

    /**
     * 更新用户状态
     *
     * @authenticated
     *
     * @urlParam id int required 用户ID
     *
     * @bodyParam status int required 状态
     *
     * @response {
     *   "code": 200,
     *   "message": "更新成功"
     * }
     */
    public function updateStatus(UpdateUserStatusRequest $request, int $id): JsonResponse
    {
            $data = $request->validated();
            $this->service->updateStatus($id, $data['status']);

            return response()->json([
                'code' => 200,
                'message' => '状态更新成功',
                'data' => null,
            ], 200);
    }

    /**
     * 重置用户密码
     *
     * @urlParam id int required 用户ID
     *
     * @bodyParam password string required 新密码
     *
     * @response {
     *   "code": 200,
     *   "message": "重置成功"
     * }
     */
    public function resetPassword(ResetPasswordRequest $request, int $id): JsonResponse
    {
            $data = $request->validated();
            $this->service->resetPassword($id, $data['password']);

            return response()->json([
                'code' => 200,
                'message' => '密码重置成功',
                'data' => null,
            ], 200);
    }
}
