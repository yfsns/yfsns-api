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

namespace App\Modules\User\Services;

use App\Modules\User\Models\UserRole;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use function in_array;

class UserRoleService
{
    /**
     * 获取角色列表（包含筛选、排序、分页逻辑）.
     */
    public function getList(array $params = []): LengthAwarePaginator
    {
        $query = UserRole::query()->withCount('users');

        // 关键词搜索
        if (! empty($params['keyword'])) {
            $keyword = trim($params['keyword']);
            $query->where(function ($q) use ($keyword): void {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('key', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // 角色类型筛选
        $type = $params['type'] ?? null;
        if ($type !== null && $type !== '') {
            $query->where('type', $type);
        }

        // 状态筛选
        $status = $params['status'] ?? null;
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        // 排序
        $sortField = $params['sort_field'] ?? $params['sortField'] ?? 'id';
        $sortOrder = strtolower($params['sort_order'] ?? $params['sortOrder'] ?? 'desc');
        if (! in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'asc';
        }

        $allowedSortFields = ['id', 'sort', 'name', 'created_at', 'updated_at'];
        if (! in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'sort';
        }

        $query->orderBy($sortField, $sortOrder);

        // 分页
        $perPage = (int) ($params['per_page'] ?? $params['perPage'] ?? 15);
        if ($perPage <= 0) {
            $perPage = 15;
        }
        $perPage = min($perPage, 100);

        $page = (int) ($params['page'] ?? 1);
        if ($page <= 0) {
            $page = 1;
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
