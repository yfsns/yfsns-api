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
use App\Modules\User\Services\AvatarReviewService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserService
{
    /**
     * @var AvatarReviewService
     */
    protected $avatarService;

    /**
     * 构造函数.
     */
    public function __construct(AvatarReviewService $avatarService)
    {
        $this->avatarService = $avatarService;
    }

    /**
     * 获取用户列表.
     */
    public function getList(array $criteria = [], int $perPage = 15)
    {
        return User::paginate($perPage);
    }

    /**
     * 获取用户详情.
     */
    public function getDetail(int $id)
    {
        return User::findOrFail($id);
    }

    /**
     * 创建用户.
     */
    public function create(array $data, ?\Illuminate\Http\Request $request = null)
    {
        // 如果提供了请求对象，记录注册IP信息
        if ($request) {
            $data = $this->recordRegisterIp($request, $data);
        }

        return User::create($data);
    }

    /**
     * 更新用户.
     */
    public function update($id, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = User::findOrFail($id);
        $user->update($data);

        return $user;
    }

    /**
     * 删除用户.
     */
    public function delete($id)
    {
        $user = User::findOrFail($id);
        return $user->delete();
    }

    /**
     * 批量更新用户状态
     */
    public function batchUpdateStatus(array $userIds, bool $status)
    {
        return User::whereIn('id', $userIds)
            ->update(['status' => $status]);
    }

    /**
     * 批量删除用户.
     */
    public function batchDelete(array $userIds)
    {
        return User::whereIn('id', $userIds)->delete();
    }

    /**
     * 查找用户.
     */
    public function find($id)
    {
        return User::findOrFail($id);
    }

    /**
     * 获取管理员用户列表.
     */
    public function getAdminUsers(array $userIds): Collection
    {
        return User::whereIn('id', $userIds)
            ->where('is_admin', true)
            ->get();
    }

    /**
     * 获取当前用户信息.
     */
    public function getCurrentUser(): ?User
    {
        return auth()->user();
    }

    /**
     * 获取当前认证用户ID.
     */
    public function getCurrentUserId(): ?int
    {
        return auth()->id();
    }

    /**
     * 检查用户是否已认证
     */
    public function isAuthenticated(): bool
    {
        return auth()->check();
    }

    /**
     * 根据邮箱查找用户.
     */
    public function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * 根据用户名查找用户.
     */
    public function findUserByUsername(string $username): ?User
    {
        return User::where('username', $username)->first();
    }

    /**
     * 检查用户是否有指定权限.
     */
    public function hasPermission(string $permission): bool
    {
        $user = $this->getCurrentUser();
        return $user && $user->hasPermission($permission);
    }

    /**
     * 检查用户是否是管理员.
     */
    public function isAdmin(): bool
    {
        $user = $this->getCurrentUser();
        return $user && $user->isAdmin();
    }

    /**
     * 检查是否为模型所有者.
     */
    public function isModelOwner($model, string $userIdField = 'user_id', ?int $userId = null): bool
    {
        $currentUserId = $userId ?? $this->getCurrentUserId();

        if (!$currentUserId) {
            return false;
        }

        return $model->{$userIdField} && (int) $model->{$userIdField} === (int) $currentUserId;
    }

    /**
     * 检查用户是否在指定用户角色中.
     */
    public function isInGroup(string $groupName): bool
    {
        $user = $this->getCurrentUser();
        return $user && $user->isInGroup($groupName);
    }

    /**
     * 更新用户资料.
     */
    public function updateProfile(array $data): User
    {
        $user = $this->getCurrentUser();
        $user->update($data);

        return $user;
    }

    /**
     * 更新用户头像（委托给 AvatarReviewService 处理）.
     */
    public function updateAvatar(UploadedFile $file): array
    {
        $user = $this->getCurrentUser();

        // 委托给 AvatarReviewService 处理审核流程
        return $this->avatarService->uploadAvatarWithReview($file, $user);
    }

    /**
     * 更新用户密码
     */
    public function updatePassword(string $password): void
    {
        $user = $this->getCurrentUser();
        $user->update([
            'password' => Hash::make($password),
        ]);
    }

    /**
     * 删除当前用户.
     */
    public function deleteCurrentUser(): void
    {
        $user = $this->getCurrentUser();

        // 删除用户头像
        if ($user->avatar) {
            Storage::delete($user->avatar);
        }

        // 删除用户
        $user->delete();
    }

    /**
     * 更新当前用户昵称.
     */
    public function updateNickname(string $nickname): User
    {
        $user = Auth::user();
        $user->update(['nickname' => $nickname]);

        return $user;
    }

    /**
     * 更新当前用户简介.
     */
    public function updateBio(string $bio): User
    {
        $user = Auth::user();
        $user->update(['bio' => $bio]);

        return $user;
    }

    /**
     * 检查用户名是否存在.
     */
    public function usernameExists(string $username): bool
    {
        return User::byUsername($username)->exists();
    }

    /**
     * 检查邮箱是否存在.
     */
    public function emailExists(string $email): bool
    {
        return User::byEmail($email)->exists();
    }

    /**
     * 检查手机号是否存在.
     */
    public function phoneExists(string $phone): bool
    {
        return User::byPhone($phone)->exists();
    }

    /**
     * 用户搜索（@弹窗）
     * 优化：返回完整的User对象，避免前端转换.
     *
     * @return Collection
     */
    public function searchUsers(string $keyword = '', int $limit = 10)
    {
        return User::query()
            ->active() // 使用 scope
            ->where(function ($q) use ($keyword): void {
                $q->where('nickname', 'like', "%$keyword%")
                    ->orWhere('username', 'like', "%$keyword%")
                    ->orWhere('email', 'like', "%$keyword%")
                    ->orWhere('id', $keyword);
            })
            ->withCount([
                'followers',
                'following',
                'posts' => function ($query): void {
                    $query->where('status', 'published');
                },
            ])
            ->limit($limit)
            ->get();
    }

    public function cancelAccount()
    {
        return $this->deleteCurrentUser();
    }

    /**
     * 获取推荐用户.
     *
     * @param int $limit 推荐数量
     *
     * @return Collection
     */
    public function getRecommendUsers(int $limit = 5)
    {
        $cacheKey = "recommended_users_{$limit}";

        return Cache::remember($cacheKey, 1800, function () use ($limit) { // 缓存30分钟
            return User::query()
                ->active() // 使用 scope
                ->orderBy('created_at', 'desc') // 按注册时间倒序，最新的用户优先
                ->limit($limit)
                ->get();
        });
    }

    /**
     * 记录注册IP信息.
     */
    protected function recordRegisterIp(\Illuminate\Http\Request $request, array $data): array
    {
        $ipLocationService = app(\App\Http\Services\IpLocationService::class);
        $clientIp = $request->ip();
        $ipLocation = $ipLocationService->getLocation($clientIp);

        // 简化：只存储省份
        return array_merge($data, [
            'register_ip' => $clientIp,
            'register_ip_location' => $ipLocation['region'] ?? '未知',
        ]);
    }
}
