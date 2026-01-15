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

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFollow extends Model
{
    /**
     * 关注状态：正常.
     */
    public const STATUS_ACTIVE = 1;

    /**
     * 关注状态：已取消.
     */
    public const STATUS_CANCELLED = 0;

    /**
     * 可批量赋值的属性.
     */
    protected $fillable = [
        'follower_id',
        'following_id',
        'status',
    ];

    /**
     * 应该被转换的属性.
     */
    protected $casts = [
        'status' => 'integer',
    ];

    /**
     * 获取关注者.
     */
    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    /**
     * 获取被关注者.
     */
    public function following(): BelongsTo
    {
        return $this->belongsTo(User::class, 'following_id');
    }

    /**
     * 关注用户.
     */
    public static function follow(User $follower, User $following): self
    {
        return static::firstOrCreate([
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ], [
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * 取消关注.
     */
    public static function unfollow(User $follower, User $following): bool
    {
        return static::where([
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ])->delete() > 0;
    }

    /**
     * 检查是否已关注.
     */
    public static function isFollowing(User $follower, User $following): bool
    {
        return static::where([
            'follower_id' => $follower->id,
            'following_id' => $following->id,
            'status' => self::STATUS_ACTIVE,
        ])->exists();
    }

    /**
     * 获取用户的关注列表（游标分页）.
     */
    public static function getFollowing(User $user, int $perPage = 20, ?string $cursor = null)
    {
        $query = static::with('following')
            ->where('follower_id', $user->id)
            ->where('status', self::STATUS_ACTIVE)
            ->orderBy('id', 'desc'); // 游标分页需要明确的排序

        return $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    /**
     * 获取用户的粉丝列表（游标分页）.
     */
    public static function getFollowers(User $user, int $perPage = 20, ?string $cursor = null)
    {
        $query = static::with('follower')
            ->where('following_id', $user->id)
            ->where('status', self::STATUS_ACTIVE)
            ->orderBy('id', 'desc'); // 游标分页需要明确的排序

        return $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }
}
