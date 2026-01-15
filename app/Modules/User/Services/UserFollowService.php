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
use App\Modules\User\Models\UserFollow;
use Illuminate\Pagination\CursorPaginator;

class UserFollowService
{
    /**
     * 关注用户.
     */
    public function follow(User $user, User $target): UserFollow
    {
        $follow = UserFollow::follow($user, $target);
        // 可扩展：加通知、积分等
        return $follow;
    }

    /**
     * 取消关注.
     */
    public function unfollow(User $user, User $target): bool
    {
        $result = UserFollow::unfollow($user, $target);
        // 可扩展：加日志等
        return $result;
    }

    /**
     * 获取关注列表（游标分页）.
     */
    public function following(User $user, int $perPage = 20, ?string $cursor = null): CursorPaginator
    {
        return UserFollow::getFollowing($user, $perPage, $cursor);
    }

    /**
     * 获取粉丝列表（游标分页）.
     */
    public function followers(User $user, int $perPage = 20, ?string $cursor = null): CursorPaginator
    {
        return UserFollow::getFollowers($user, $perPage, $cursor);
    }

    /**
     * 是否已关注.
     */
    public function isFollowing(User $user, User $target): bool
    {
        return UserFollow::isFollowing($user, $target);
    }

    /**
     * 获取用户的关注统计信息.
     */
    public function getUserFollowStats(User $user): array
    {
        return [
            'following' => $this->getFollowingCount($user),
            'followers' => $this->getFollowersCount($user),
        ];
    }

    /**
     * 获取用户的关注数.
     */
    public function getFollowingCount(User $user): int
    {
        return UserFollow::where('follower_id', $user->id)
            ->where('status', UserFollow::STATUS_ACTIVE)
            ->count();
    }

    /**
     * 获取用户的粉丝数.
     */
    public function getFollowersCount(User $user): int
    {
        return UserFollow::where('following_id', $user->id)
            ->where('status', UserFollow::STATUS_ACTIVE)
            ->count();
    }
}
