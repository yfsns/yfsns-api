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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UserPermissionController extends Controller
{
    /**
     * 获取权限列表（从配置文件读取，权限点独立，不分角色）.
     */
    public function index(Request $request): JsonResponse
    {
        $permissions = $this->getPermissionsFromConfig();

        // 按模块筛选
        if ($request->module) {
            $permissions = $permissions->where('module', $request->module);
        }

        // 关键词搜索
        if ($request->keyword) {
            $keyword = strtolower($request->keyword);
            $permissions = $permissions->filter(function ($perm) use ($keyword) {
                return str_contains(strtolower($perm['name']), $keyword) ||
                       str_contains(strtolower($perm['slug']), $keyword) ||
                       str_contains(strtolower($perm['description'] ?? ''), $keyword);
            });
        }

        // 排序
        $permissions = $permissions->sortBy([
            ['module', 'asc'],
            ['slug', 'asc'],
        ])->values();

        // 分页
        $perPage = (int) ($request->per_page ?? 15);
        $page = (int) ($request->page ?? 1);
        $total = $permissions->count();
        $items = $permissions->forPage($page, $perPage)->values();

        // 使用统一的分页响应格式（保持原有壳结构）
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => $items,
                'page' => $page,
                'perPage' => $perPage,
                'lastPage' => ceil($total / $perPage),
                'total' => $total,
                'hasMore' => $page < ceil($total / $perPage),
            ],
        ], 200);
    }

    /**
     * 获取权限树形结构（按模块分组，权限点独立，不分角色）.
     */
    public function tree(): JsonResponse
    {
        $permissions = $this->getPermissionsFromConfig()
            ->groupBy('module')
            ->map(function ($items, $module) {
                return [
                    'module' => $module,
                    'permissions' => $items->map(function ($perm) {
                        return [
                            'slug' => $perm['slug'],
                            'name' => $perm['name'],
                            'description' => $perm['description'] ?? null,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $permissions,
        ], 200);
    }

    /**
     * 显示权限详情（从配置文件读取）.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $permissions = $this->getPermissionsFromConfig();
        $permission = $permissions->firstWhere('slug', $slug);

        if (! $permission) {
            return response()->json([
                'code' => 404,
                'message' => '权限不存在',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $permission,
        ], 200);
    }

    /**
     * 创建权限（已废弃，权限定义在配置文件中）.
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'code' => 400,
            'message' => '权限定义在配置文件中，不支持动态创建',
            'data' => null,
        ], 400);
    }

    /**
     * 更新权限（已废弃，权限定义在配置文件中）.
     */
    public function update(Request $request, string $slug): JsonResponse
    {
        return response()->json([
            'code' => 400,
            'message' => '权限定义在配置文件中，不支持动态更新',
            'data' => null,
        ], 400);
    }

    /**
     * 删除权限（已废弃，权限定义在配置文件中）.
     */
    public function destroy(string $slug): JsonResponse
    {
        return response()->json([
            'code' => 400,
            'message' => '权限定义在配置文件中，不支持动态删除',
            'data' => null,
        ], 400);
    }

    /**
     * 从配置文件读取权限定义.
     */
    private function getPermissionsFromConfig(): Collection
    {
        $definitions = config('role_permissions', []);
        $permissions = collect();

        foreach ($definitions as $module => $items) {
            foreach ($items as $item) {
                $permissions->push([
                    'module' => $module,
                    'slug' => $item['slug'],
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                ]);
            }
        }

        return $permissions;
    }
}
