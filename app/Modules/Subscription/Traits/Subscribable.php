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

namespace App\Modules\Subscription\Traits;

use App\Modules\Subscription\Models\Subscription;

use function get_class;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * 可订阅 Trait.
 *
 * 使用此 Trait 的模型可以被用户订阅
 */
trait Subscribable
{
    /**
     * 获取所有订阅记录.
     */
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    /**
     * 获取有效的订阅记录.
     */
    public function activeSubscriptions(): MorphMany
    {
        return $this->subscriptions()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('expired_at', '>', now());
    }

    /**
     * 获取订阅者列表.
     */
    public function subscribers()
    {
        return $this->morphToMany(
            \App\Modules\User\Models\User::class,
            'subscribable',
            'subscriptions',
            'subscribable_id',
            'user_id'
        )->wherePivot('status', Subscription::STATUS_ACTIVE)
            ->wherePivot('expired_at', '>', now());
    }

    /**
     * 检查用户是否订阅了.
     */
    public function isSubscribedBy(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        // 直接查询订阅表
        return Subscription::where('user_id', $userId)
            ->where('subscribable_type', get_class($this))
            ->where('subscribable_id', $this->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('expired_at', '>', now())
            ->exists();
    }

    /**
     * 获取订阅数量.
     */
    public function getSubscriptionCount(): int
    {
        return $this->activeSubscriptions()->count();
    }
}
