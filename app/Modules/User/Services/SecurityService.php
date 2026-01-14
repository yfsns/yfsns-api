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
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SecurityService
{
    /**
     * 绑定手机号.
     */
    public function bindPhone(User $user, array $data): bool
    {
        // 验证验证码
        // TODO: 实现验证码验证逻辑

        return DB::transaction(function () use ($user, $data) {
            $user->phone = $data['phone'];

            return $user->save();
        });
    }

    /**
     * 解绑手机号.
     */
    public function unbindPhone(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $user->phone = null;

            return $user->save();
        });
    }

    /**
     * 绑定或换绑邮箱.
     */
    public function bindEmail(User $user, array $data): array
    {
        // 验证验证码
        // TODO: 实现验证码验证逻辑（从Redis验证）

        $isRebind = ! empty($user->email);
        $oldEmail = $user->email;

        return DB::transaction(function () use ($user, $data, $isRebind, $oldEmail) {
            $user->email = $data['email'];
            $user->save();

            return [
                'email' => $user->email,
                'is_rebind' => $isRebind,
                'old_email' => $oldEmail,
            ];
        });
    }

    /**
     * 绑定微信
     */
    public function bindWechat(User $user, array $data): bool
    {
        // 处理微信授权
        // TODO: 实现微信授权逻辑

        return DB::transaction(function () use ($user) {
            // TODO: 保存微信相关信息
            return $user->save();
        });
    }

    /**
     * 解绑微信
     */
    public function unbindWechat(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            // TODO: 清除微信相关信息
            return $user->save();
        });
    }

    /**
     * 修改密码
     */
    public function changePassword(User $user, array $data): bool
    {
        // 验证原密码
        if (! Hash::check($data['old_password'], $user->password)) {
            throw new Exception('原密码错误');
        }

        return DB::transaction(function () use ($user, $data) {
            $user->password = Hash::make($data['new_password']);

            return $user->save();
        });
    }
}
