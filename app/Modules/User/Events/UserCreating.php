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

namespace App\Modules\User\Events;

use App\Modules\User\Models\User;
use App\Modules\User\Models\UserRole;
use Exception;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UserCreating
{
    use SerializesModels;

    /**
     * 处理事件.
     */
    public function handle(User $user): void
    {
        try {
            // 获取默认用户角色（注册用户）
            $userGroup = UserRole::where('key', 'user')->first();

            if (! $userGroup) {
                Log::error('普通用户组未找到', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                ]);

                return;
            }

            // 设置默认用户角色
            $user->role_id = $userGroup->id;

            Log::info('用户已分配到默认用户角色', [
                'user_id' => $user->id,
                'username' => $user->username,
                'role_id' => $userGroup->id,
                'group_name' => $userGroup->name,
            ]);
        } catch (Exception $e) {
            Log::error('设置用户默认组失败', [
                'user_id' => $user->id,
                'username' => $user->username,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
