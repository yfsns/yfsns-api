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
use App\Modules\User\Models\UserRole;
use App\Modules\User\Requests\Admin\BatchDestroyRolesRequest;
use App\Modules\User\Requests\Admin\UpdateRolePermissionsRequest;
use App\Modules\User\Requests\Admin\UpdateRoleStatusRequest;
use App\Modules\User\Requests\UserRoleRequest;
use App\Modules\User\Resources\UserRoleResource;
use App\Modules\User\Services\UserRoleService;

use const ARRAY_FILTER_USE_BOTH;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use function in_array;

/**
 * 用户角色管理控制器.
 *
 * @group 后台管理-用户角色管理
 */
class UserRoleController extends Controller
{

    protected UserRoleService $service;

    public function __construct(UserRoleService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取角色列表.
     *
     * @authenticated
     *
     * @queryParam keyword string 搜索关键词
     * @queryParam type integer 角色类型
     * @queryParam status integer 状态
     * @queryParam per_page integer 每页数量
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "name": "管理员",
     *         "key": "admin",
     *         "description": "系统管理员",
     *         "permissions": [],
     *         "type": 1,
     *         "status": 1,
     *         "is_system": true,
     *         "is_default": false
     *       }
     *     ]
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        // 手动处理参数，支持驼峰和下划线两种格式
        $allParams = $request->all();

        // 转换驼峰格式为下划线格式
        $params = [
            'keyword' => $allParams['keyword'] ?? null,
            'type' => isset($allParams['type']) ? (int) $allParams['type'] : null,
            'status' => isset($allParams['status']) ? (int) $allParams['status'] : null,
            'sort_field' => $allParams['sort_field'] ?? $allParams['sortField'] ?? 'id',
            'sort_order' => $allParams['sort_order'] ?? $allParams['sortOrder'] ?? 'desc',
            'page' => (int) ($allParams['page'] ?? 1),
            'per_page' => (int) ($allParams['per_page'] ?? $allParams['perPage'] ?? 15),
        ];

        // 清理空值（但保留 0 值）
        $params = array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        }, ARRAY_FILTER_USE_BOTH);

        // 处理分页参数（限制为允许的值）
        $allowedPerPage = [10, 20, 50, 100];
        if (! in_array($params['per_page'], $allowedPerPage, true)) {
            $params['per_page'] = 15;
        }

        // 处理排序字段（限制为允许的字段）
        $allowedSortFields = ['id', 'sort', 'name', 'created_at', 'updated_at'];
        if (! in_array($params['sort_field'], $allowedSortFields, true)) {
            $params['sort_field'] = 'id';
        }

        // 处理排序方向
        if (! in_array(strtolower($params['sort_order']), ['asc', 'desc'], true)) {
            $params['sort_order'] = 'desc';
        }

        $roles = $this->service->getList($params);

        // 保持原有分页响应壳结构
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => UserRoleResource::collection($roles->items()),
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
                'from' => $roles->firstItem(),
                'to' => $roles->lastItem(),
                'prev_page_url' => $roles->previousPageUrl(),
                'next_page_url' => $roles->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * 创建角色.
     *
     * @authenticated
     *
     * @bodyParam name string required 角色名称
     * @bodyParam key string required 角色标识
     * @bodyParam description string 角色描述
     * @bodyParam permissions array 权限列表
     * @bodyParam type integer 角色类型
     * @bodyParam status integer 状态
     * @bodyParam sort integer 排序
     *
     * @response {
     *   "code": 200,
     *   "message": "创建成功",
     *   "data": {
     *     "id": 1,
     *     "name": "新角色",
     *     "key": "new_role"
     *   }
     * }
     */
    public function store(UserRoleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);

        $data['is_system'] = false; // 新创建的角色不是系统角色
        if (empty($data['key']) && ! empty($data['name'])) {
            $data['key'] = $this->generateRoleKey($data['name']);
        }

        $role = UserRole::create($data);
        $role->syncPermissions($permissions);

        return response()->json([
            'code' => 200,
            'message' => '角色创建成功',
            'data' => new UserRoleResource($role->refresh()->loadCount('users')),
        ], 200);
    }

    /**
     * 获取角色详情.
     *
     * @authenticated
     *
     * @urlParam role integer required 角色ID
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "id": 1,
     *     "name": "管理员",
     *     "key": "admin"
     *   }
     * }
     */
    public function show(UserRole $role): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => new UserRoleResource($role->loadCount('users')),
        ], 200);
    }

    /**
     * 更新角色.
     *
     * @authenticated
     *
     * @urlParam role integer required 角色ID
     *
     * @bodyParam name string 角色名称
     * @bodyParam key string 角色标识
     * @bodyParam description string 角色描述
     * @bodyParam permissions array 权限列表
     * @bodyParam type integer 角色类型
     * @bodyParam status integer 状态
     * @bodyParam sort integer 排序
     *
     * @response {
     *   "code": 200,
     *   "message": "更新成功",
     *   "data": {
     *     "id": 1,
     *     "name": "更新后的角色名"
     *   }
     * }
     */
    public function update(UserRoleRequest $request, UserRole $role): JsonResponse
    {
        $data = $request->validated();
        $permissions = $data['permissions'] ?? null;
        unset($data['permissions']);

        // 系统预设角色不允许修改key字段
        if ($role->isSystemPresetRole()) {
            unset($data['key']);
        }

        $role->update($data);

        if ($permissions !== null) {
            $role->syncPermissions($permissions);
        }

        return response()->json([
            'code' => 200,
            'message' => '角色更新成功',
            'data' => new UserRoleResource($role->refresh()->loadCount('users')),
        ], 200);
    }

    /**
     * 更新角色权限（独立接口，便于前端单独提交）.
     *
     * @authenticated
     *
     * @urlParam role integer required 角色ID
     *
     * @bodyParam permissions array required 权限标识数组
     */
    public function updatePermissions(UpdateRolePermissionsRequest $request, UserRole $role): JsonResponse
    {
        $data = $request->validated();
        $role->syncPermissions($data['permissions']);

        return response()->json([
            'code' => 200,
            'message' => '权限更新成功',
            'data' => new UserRoleResource($role->refresh()->loadCount('users')),
        ], 200);
    }

    /**
     * 删除角色.
     *
     * @authenticated
     *
     * @urlParam role integer required 角色ID
     *
     * @response {
     *   "code": 200,
     *   "message": "删除成功"
     * }
     */
    public function destroy(UserRole $role): JsonResponse
    {
        // 检查是否允许删除
        if (! $role->canBeDeleted()) {
            return response()->json([
                'code' => 400,
                'message' => $role->getDeleteRestrictionReason(),
                'data' => null,
            ], 400);
        }

        $role->delete();

        return response()->json([
            'code' => 200,
            'message' => '角色删除成功',
            'data' => null,
        ], 200);
    }

    /**
     * 批量删除角色.
     *
     * @authenticated
     *
     * @bodyParam ids array required 角色ID列表
     *
     * @response {
     *   "code": 200,
     *   "message": "删除成功"
     * }
     */
    public function batchDestroy(BatchDestroyRolesRequest $request): JsonResponse
    {
        $data = $request->validated();

        $roles = UserRole::whereIn('id', $data['ids'])->get();

        // 检查是否有角色不允许删除
        $restrictedRoles = $roles->filter(function ($role) {
            return ! $role->canBeDeleted();
        });

        if ($restrictedRoles->isNotEmpty()) {
            $restrictedRoleNames = $restrictedRoles->pluck('name')->implode('、');
            $reasons = $restrictedRoles->map(function ($role) {
                return $role->getDeleteRestrictionReason();
            })->unique()->implode('；');

            return response()->json([
                'code' => 400,
                'message' => "角色 [{$restrictedRoleNames}] 无法删除：{$reasons}",
                'data' => null,
            ], 400);
        }

        UserRole::whereIn('id', $data['ids'])->delete();

        return response()->json([
            'code' => 200,
            'message' => '角色批量删除成功',
            'data' => null,
        ], 200);
    }

    /**
     * 更新角色状态
     *
     * @authenticated
     *
     * @urlParam role integer required 角色ID
     *
     * @bodyParam status integer required 状态
     *
     * @response {
     *   "code": 200,
     *   "message": "状态更新成功"
     * }
     */
    public function updateStatus(UpdateRoleStatusRequest $request, UserRole $role): JsonResponse
    {
        $data = $request->validated();

        // 系统预设角色不允许禁用
        if ($role->isSystemPresetRole() && $data['status'] == 0) {
            return response()->json([
                'code' => 400,
                'message' => '系统预设角色不允许禁用',
                'data' => null,
            ], 400);
        }

        $role->update(['status' => $data['status']]);

        return response()->json([
            'code' => 200,
            'message' => '角色状态更新成功',
            'data' => null,
        ], 200);
    }

    protected function generateRoleKey(string $name): string
    {
        $base = Str::slug($name, '_');
        $base = preg_replace('/[^a-z0-9_]/', '', strtolower($base));

        if (empty($base) || ! preg_match('/^[a-z]/', $base)) {
            $base = 'role_' . strtolower(Str::random(5));
        }

        $candidate = $base;
        $suffix = 1;
        while (UserRole::withTrashed()->where('key', $candidate)->exists()) {
            $candidate = "{$base}_{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
}
