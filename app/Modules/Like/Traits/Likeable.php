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

namespace App\Modules\Like\Traits;

use App\Modules\Like\Models\Like;
use App\Modules\User\Models\User;

use function get_class;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Schema;

trait Likeable
{
    /**
     * 获取点赞记录.
     * 注意：LikeService 使用简单类型（如 'post'）存储，而不是完整类名.
     * 因此使用 hasMany 并手动指定类型条件，而不是使用 morphMany.
     */
    public function likes(): HasMany
    {
        $modelType = $this->getLikeableType();
        
        return $this->hasMany(Like::class, 'likeable_id')
            ->where('likeable_type', $modelType);
    }

    /**
     * 获取点赞类型标识符（简单类型）.
     */
    private function getLikeableType(): string
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

    /**
     * 获取点赞用户.
     */
    public function likers()
    {
        return $this->belongsToMany(User::class, 'likes', 'likeable_id', 'user_id')
            ->where('likeable_type', get_class($this))
            ->withTimestamps();
    }

    /**
     * 增加点赞计数.
     */
    public function incrementLikeCount(): void
    {
        if (Schema::hasColumn($this->getTable(), 'like_count')) {
            $this->increment('like_count');
            $this->refresh();
        }
    }

    /**
     * 减少点赞计数.
     */
    public function decrementLikeCount(): void
    {
        if (Schema::hasColumn($this->getTable(), 'like_count')) {
            $this->decrement('like_count');
            $this->refresh();
        }
    }

    /**
     * 获取点赞数量.
     */
    public function getLikeCount(): int
    {
        return $this->likes()->count();
    }

    /**
     * 检查是否被指定用户点赞.
     */
    public function isLikedBy(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }
}
