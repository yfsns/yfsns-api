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

namespace App\Modules\Like\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Like extends Model
{
    protected $fillable = [
        'user_id',
        'likeable_id',
        'likeable_type',
        'type',
    ];

    /**
     * 多态关联的类型映射.
     */
    protected $morphClassMap = [
        'post' => \App\Modules\Post\Models\Post::class,
        'comment' => \App\Modules\Comment\Models\Comment::class,
        'topic' => \App\Modules\Topic\Models\Topic::class,
        'user' => User::class,
        'forum_thread' => \App\Modules\Forum\Models\ForumThread::class,
        'forum_threadreply' => \App\Modules\Forum\Models\ForumThreadReply::class,
        'article' => \App\Modules\Article\Models\Article::class,
    ];

    /**
     * 点赞用户.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 被点赞的内容（多态关联）.
     */
    public function likeable(): MorphTo
    {
        return $this->morphTo('likeable', 'likeable_type', 'likeable_id', 'id', $this->morphClassMap);
    }
}
