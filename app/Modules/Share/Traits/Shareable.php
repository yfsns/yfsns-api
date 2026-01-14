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

namespace App\Modules\Share\Traits;

use App\Modules\Share\Models\Share;
use App\Modules\User\Models\User;

use function get_class;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Shareable
{
    /**
     * 获取分享记录.
     */
    public function shares(): MorphMany
    {
        return $this->morphMany(Share::class, 'shareable');
    }

    /**
     * 获取分享用户.
     */
    public function sharers()
    {
        return $this->belongsToMany(User::class, 'shares', 'shareable_id', 'user_id')
            ->where('shareable_type', get_class($this))
            ->withTimestamps();
    }

    /**
     * 增加分享计数.
     */
    public function incrementShareCount(): void
    {
        if (property_exists($this, 'share_count')) {
            $this->increment('share_count');
        }
    }

    /**
     * 获取分享数量.
     */
    public function getShareCount(): int
    {
        return $this->shares()->count();
    }

    /**
     * 检查是否被指定用户分享.
     */
    public function isSharedBy(User $user): bool
    {
        return $this->shares()->where('user_id', $user->id)->exists();
    }

    /**
     * 获取分享链接.
     */
    public function getShareUrl(string $platform = 'default'): string
    {
        $baseUrl = config('app.url');
        $type = strtolower(class_basename($this));

        return "{$baseUrl}/share/{$type}/{$this->id}?platform={$platform}";
    }
}
