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

namespace App\Modules\Collect\Traits;

use App\Modules\Collect\Models\Collect;
use App\Modules\User\Models\User;

use function get_class;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Schema;

trait Collectable
{
    /**
     * 获取收藏记录.
     * 注意：CollectService 使用简单类型（如 'post'）存储，而不是完整类名.
     * 因此使用 hasMany 并手动指定类型条件，而不是使用 morphMany.
     */
    public function collects(): HasMany
    {
        $modelType = $this->getCollectableTypeForCollect();
        
        return $this->hasMany(Collect::class, 'collectable_id')
            ->where('collectable_type', $modelType);
    }

    /**
     * 获取收藏用户.
     */
    public function collectors()
    {
        return $this->belongsToMany(User::class, 'collects', 'collectable_id', 'user_id')
            ->where('collectable_type', get_class($this))
            ->withTimestamps();
    }

    /**
     * 增加收藏计数.
     */
    public function incrementCollectCount(): void
    {
        // 检查数据库表是否有 collect_count 字段
        if (Schema::hasColumn($this->getTable(), 'collect_count')) {
            $this->increment('collect_count');
            // 刷新模型，确保获取最新值
            $this->refresh();
        }
    }

    /**
     * 减少收藏计数.
     */
    public function decrementCollectCount(): void
    {
        // 检查数据库表是否有 collect_count 字段
        if (Schema::hasColumn($this->getTable(), 'collect_count')) {
            $this->decrement('collect_count');
            // 刷新模型，确保获取最新值
            $this->refresh();
        }
    }

    /**
     * 同步收藏计数（确保数据一致性）.
     */
    public function syncCollectCount(): void
    {
        if (Schema::hasColumn($this->getTable(), 'collect_count')) {
            $actualCollectCount = Collect::where('collectable_id', $this->id)
                ->where('collectable_type', $this->getCollectableTypeForCollect())
                ->count();

            \Illuminate\Support\Facades\DB::update(
                'UPDATE ' . $this->getTable() . ' SET collect_count = ? WHERE id = ?',
                [$actualCollectCount, $this->id]
            );

            $rawData = \Illuminate\Support\Facades\DB::table($this->getTable())->where('id', $this->id)->first();
            $this->setRawAttributes((array) $rawData);
        }
    }

    /**
     * 获取收藏数量.
     */
    public function getCollectCount(): int
    {
        return $this->collects()->count();
    }

    /**
     * 检查是否被指定用户收藏.
     */
    public function isCollectedBy(User $user): bool
    {
        return $this->collects()->where('user_id', $user->id)->exists();
    }

    /**
     * 获取收藏类型标识符.
     */
    private function getCollectableTypeForCollect(): string
    {
        return match (get_class($this)) {
            \App\Modules\Post\Models\Post::class => 'post',
            \App\Modules\Comment\Models\Comment::class => 'comment',
            \App\Modules\Topic\Models\Topic::class => 'topic',
            User::class => 'user',
            \App\Modules\Forum\Models\ForumThread::class => 'forum_thread',
            \App\Modules\Forum\Models\ForumThreadReply::class => 'forum_threadreply',
            \App\Modules\Article\Models\Article::class => 'article',
            default => strtolower(class_basename($this))
        };
    }
}
