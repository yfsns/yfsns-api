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

namespace App\Modules\Subscription\Services;

use App\Modules\Subscription\Models\Subscription;
use App\Modules\User\Models\User;
use Carbon\Carbon;

use function get_class;

use Illuminate\Database\Eloquent\Model;

/**
 * 订阅服务
 */
class SubscriptionService
{
    /**
     * 创建订阅.
     *
     * @param User     $user         订阅用户
     * @param Model    $subscribable 被订阅对象
     * @param float    $price        订阅价格
     * @param int      $days         订阅天数
     * @param null|int $orderId      关联订单ID
     */
    public function subscribe(
        User $user,
        Model $subscribable,
        float $price = 0,
        int $days = 365,
        ?int $orderId = null
    ): Subscription {
        // 检查是否已有有效订阅
        $existingSubscription = Subscription::where('user_id', $user->id)
            ->where('subscribable_type', get_class($subscribable))
            ->where('subscribable_id', $subscribable->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('expired_at', '>', now())
            ->first();

        if ($existingSubscription) {
            // 如果已有订阅，延长时间
            $existingSubscription->expired_at = Carbon::parse($existingSubscription->expired_at)->addDays($days);
            $existingSubscription->save();

            return $existingSubscription;
        }

        // 创建新订阅
        return Subscription::create([
            'user_id' => $user->id,
            'subscribable_type' => get_class($subscribable),
            'subscribable_id' => $subscribable->id,
            'order_id' => $orderId,
            'price' => $price,
            'started_at' => now(),
            'expired_at' => now()->addDays($days),
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }

    /**
     * 取消订阅.
     */
    public function unsubscribe(User $user, Model $subscribable): bool
    {
        return Subscription::where('user_id', $user->id)
            ->where('subscribable_type', get_class($subscribable))
            ->where('subscribable_id', $subscribable->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->update(['status' => Subscription::STATUS_CANCELLED]) > 0;
    }

    /**
     * 检查用户是否订阅了指定对象
     */
    public function checkSubscription(User $user, Model $subscribable): bool
    {
        return Subscription::where('user_id', $user->id)
            ->where('subscribable_type', get_class($subscribable))
            ->where('subscribable_id', $subscribable->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('expired_at', '>', now())
            ->exists();
    }

    /**
     * 获取用户的订阅记录.
     */
    public function getUserSubscription(User $user, Model $subscribable): ?Subscription
    {
        return Subscription::where('user_id', $user->id)
            ->where('subscribable_type', get_class($subscribable))
            ->where('subscribable_id', $subscribable->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('expired_at', '>', now())
            ->first();
    }

    /**
     * 获取用户的所有订阅.
     */
    public function getUserSubscriptions(User $user, int $perPage = 20)
    {
        return Subscription::where('user_id', $user->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('expired_at', '>', now())
            ->with('subscribable')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * 标记过期的订阅.
     */
    public function markExpiredSubscriptions(): int
    {
        return Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('expired_at', '<=', now())
            ->update(['status' => Subscription::STATUS_EXPIRED]);
    }
}
