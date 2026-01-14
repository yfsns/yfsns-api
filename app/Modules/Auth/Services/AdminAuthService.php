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

namespace App\Modules\Auth\Services;

use App\Exceptions\AuthException;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * 管理员认证服务 - 专门为管理员登录优化，去除IP定位、通知等耗时操作
 */
class AdminAuthService
{
    /**
     * 管理员登录（简化版，避免多表查询和耗时操作）
     */
    public function login(array $data, ?Request $request = null): array
    {
        // 仅支持用户名+密码登录，避免多表查询
        $username = $data['username'] ?? null;
        if (!$username) {
            throw AuthException::invalidCredentials();
        }

        Log::info('AdminAuthService login', ['username' => $username]);

        // 直接通过用户名查询管理员用户，添加is_admin条件避免普通用户数据泄露
        $user = User::where('username', $username)
            ->where('is_admin', true)
            ->where('status', 1) // 只查询启用状态的用户
            ->first();

        // 验证用户存在且密码正确
        if (!$user || !\Hash::check($data['password'], $user->password)) {
            Log::warning('Admin authentication failed', ['username' => $username]);
            throw AuthException::invalidCredentials();
        }

        Log::info('Admin authenticated', ['user_id' => $user->id, 'username' => $user->username]);

        // 简单的登录信息更新（不包含IP定位）
        $user->update([
            'last_login_at' => now(),
        ]);

        // 管理员统一使用web guard（session认证）
        Auth::guard('web')->login($user);

        // 返回简化数据，不包含token相关信息
        return [
            'user' => $user,
        ];
    }

    /**
     * 获取当前管理员信息
     */
    public function me(): ?User
    {
        return Auth::guard('web')->user();
    }

    /**
     * 管理员登出
     */
    public function logout(User $user): void
    {
        Auth::guard('web')->logout();

        // 使当前session无效，防止session fixation攻击
        session()->invalidate();

        // 重新生成session ID，防止session hijacking
        session()->regenerateToken();
    }
}