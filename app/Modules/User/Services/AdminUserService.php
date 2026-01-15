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

use App\Modules\User\Models\User;

use function array_key_exists;

use Exception;
use Illuminate\Pagination\LengthAwarePaginator;

use function in_array;

class AdminUserService
{
    public function __construct()
    {
    }

    /**
     * 获取用户列表.
     */
    public function getList(array $params): LengthAwarePaginator
    {
        $query = User::query()->with('role:id,key,name');

        // 搜索条件
        if (!empty($params['keyword'])) {
            $keyword = trim($params['keyword']);
            $query->where(function ($q) use ($keyword): void {
                $q->where('username', 'like', "%{$keyword}%")
                    ->orWhere('nickname', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        // 状态筛选
        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }

        // 角色筛选
        if (isset($params['role_id'])) {
            $query->where('role_id', $params['role_id']);
        }

        // 排序
        $sortField = $params['sort_field'] ?? 'id';
        $sortOrder = in_array(strtolower($params['sort_order'] ?? 'desc'), ['asc', 'desc'])
            ? strtolower($params['sort_order'])
            : 'desc';
        $query->orderBy($sortField, $sortOrder);

        // 分页
        $perPage = max(1, (int)($params['per_page'] ?? 15));

        return $query->paginate($perPage, ['*'], 'page', $params['page'] ?? 1);
    }

    /**
     * 获取用户详情.
     */
    public function getDetail(int $id): User
    {
        return User::query()
            ->with('role:id,key,name')
            ->findOrFail($id);
    }

    /**
     * 创建用户.
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * 更新用户.
     */
    public function update(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->update($data);

        return $user;
    }

    /**
     * 删除用户.
     */
    public function delete(int $id): void
    {
        $user = User::findOrFail($id);
        $user->delete();
    }

    /**
     * 批量删除用户.
     */
    public function batchDelete(array $ids): void
    {
        User::whereIn('id', $ids)->delete();
    }

    /**
     * 更新用户状态
     */
    public function updateStatus(int $id, int $status): void
    {
        $user = User::findOrFail($id);

        // 管理员不允许禁用
        if ($user->is_admin && $status == 0) {
            throw new Exception('管理员不允许禁用');
        }

        $user->update(['status' => $status]);
    }

    /**
     * 更新用户角色.
     */
    public function updateRoles(int $id, array $roleIds): void
    {
        // 目前无角色体系，直接跳过
        // $user = $this->find($id);
        // $user->roles()->sync($roleIds);
        // 或者抛出异常提示
        // throw new \Exception('当前系统未启用角色体系');
    }

    /**
     * 重置用户密码
     */
    public function resetPassword(int $id, string $newPassword): void
    {
        $user = User::findOrFail($id);
        $user->update(['password' => bcrypt($newPassword)]);
    }
}
