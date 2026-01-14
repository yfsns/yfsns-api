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

namespace App\Modules\Comment\Models;

use App\Http\Traits\IpRecordTrait;
use App\Modules\File\Models\File;
use App\Modules\Review\Traits\HasReviewable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, HasReviewable, IpRecordTrait, SoftDeletes;

    // 内容类型
    public const CONTENT_TYPE_TEXT = 'text';

    public const CONTENT_TYPE_IMAGE = 'image';

    public const CONTENT_TYPE_VIDEO = 'video';

    // 状态（与 Post 统一）
    public const STATUS_PENDING = 0;     // 待审核

    public const STATUS_PUBLISHED = 1;    // 已发布（审核通过/正常）

    public const STATUS_REJECTED = 2;     // 审核拒绝

    // 兼容旧的状态值（向后兼容）
    public const STATUS_NORMAL = 1;      // 正常（审核通过）- 等同于 STATUS_PUBLISHED

    protected $fillable = [
        'target_id',
        'target_type',
        'user_id',
        'parent_id',
        'content',
        'content_type',
        'images',
        'video_url',
        'like_count',
        'reply_count',
        'hot_score',
        'status',
        'ip',
        'ip_country',
        'ip_region',
        'ip_city',
        'ip_isp',
        'ip_location',
        'user_agent',
        'published_at',
        'audited_at',
    ];

    protected $casts = [
        'images' => 'array',
        'like_count' => 'integer',
        'reply_count' => 'integer',
        'hot_score' => 'integer',
        'status' => 'integer',
        'published_at' => 'datetime',
        'audited_at' => 'datetime',
    ];

    // 关联目标（文章、帖子、动态等）
    public function target()
    {
        return $this->morphTo();
    }

    // 关联用户
    public function user()
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class);
    }

    // 关联点赞（使用多态关联）
    public function likes()
    {
        // 使用自定义查询来处理简短别名的问题
        $instance = new \App\Modules\Like\Models\Like;
        return $instance->newQuery()
            ->where('likeable_type', 'comment')
            ->where('likeable_id', $this->getKey());
    }

    // 获取所有父评论
    public function ancestors()
    {
        return $this->belongsToMany(
            self::class,
            'comment_relations',
            'descendant',
            'ancestor'
        )->withPivot('depth');
    }

    // 获取所有子评论
    public function descendants()
    {
        return $this->belongsToMany(
            self::class,
            'comment_relations',
            'ancestor',
            'descendant'
        )->withPivot('depth');
    }

    // 获取直接回复
    public function replies()
    {
        return $this->descendants()
            ->wherePivot('depth', 1)
            ->orderBy('created_at', 'asc');
    }

    // 获取状态文本
    public function getStatusTextAttribute(): string
    {
        return self::getStatusText($this->status);
    }

    /**
     * 获取指定用户是否点赞了此评论
     */
    public function getIsLikedAttribute($user = null): bool
    {
        // 如果没有传入用户，则尝试从认证中获取
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return false;
        }

        // 直接查询数据库（因为likes()现在返回查询构建器）
        return \App\Modules\Like\Models\Like::where('likeable_type', 'comment')
            ->where('likeable_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * 获取状态文本（静态方法，用于复用状态转换逻辑）.
     *
     * @param int $status 状态值
     *
     * @return string 状态文本
     */
    public static function getStatusText(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => '待审核',
            self::STATUS_PUBLISHED, self::STATUS_NORMAL => '已发布',
            self::STATUS_REJECTED => '审核拒绝',
            default => '未知',
        };
    }

    // 判断是否已发布
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED || $this->status === self::STATUS_NORMAL;
    }

    // 判断是否删除（通过软删除的deleted_at字段）
    public function isDeleted(): bool
    {
        return $this->trashed();
    }

    // 判断是否待审核
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    // 判断是否被拒绝
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * 获取评论的所有文件.
     */
    public function files()
    {
        return $this->belongsToMany(File::class, 'comment_file')
            ->withPivot('type', 'sort')
            ->withTimestamps();
    }

    /**
     * 获取评论的图片.
     */
    public function images()
    {
        return $this->belongsToMany(File::class, 'comment_file')
            ->wherePivot('type', 'content')
            ->where('type', 'image')
            ->withPivot('sort')
            ->withTimestamps();
    }

    /**
     * 关联@用户.
     */
    public function mentions()
    {
        return $this->hasMany(\App\Modules\User\Models\UserMention::class, 'content_id')
            ->where('content_type', \App\Modules\User\Models\UserMention::TYPE_COMMENT);
    }

    /**
     * 关联话题标签（复用post_topics表）
     * 注意：post_id字段实际存储comment_id.
     */
    public function topics()
    {
        return $this->belongsToMany(
            \App\Modules\Topic\Models\Topic::class,
            'post_topics',
            'post_id',  // 实际存储comment_id
            'topic_id'
        )->withPivot('position')->withTimestamps();
    }

    /**
     * 获取评论的视频.
     */
    public function videos()
    {
        return $this->belongsToMany(File::class, 'comment_file')
            ->wherePivot('type', 'content')
            ->where('type', 'video')
            ->withPivot('sort')
            ->withTimestamps();
    }

    /**
     * 获取评论的附件.
     */
    public function attachments()
    {
        return $this->belongsToMany(File::class, 'comment_file')
            ->wherePivot('type', 'attachment')
            ->withPivot('sort')
            ->withTimestamps();
    }

    /**
     * 增加点赞计数.
     */
    public function incrementLikeCount(): void
    {
        // 合并更新 like_count 和 hot_score，减少数据库操作
        \Illuminate\Support\Facades\DB::update(
            'UPDATE comments SET 
                like_count = like_count + 1,
                hot_score = (like_count + 1) * 2 + reply_count
            WHERE id = ?',
            [$this->id]
        );
        // 强制重新加载模型属性
        $rawData = \Illuminate\Support\Facades\DB::table('comments')->where('id', $this->id)->first();
        $this->setRawAttributes((array) $rawData);
    }

    /**
     * 减少点赞计数.
     */
    public function decrementLikeCount(): void
    {
        // 合并更新 like_count 和 hot_score，减少数据库操作
        \Illuminate\Support\Facades\DB::update(
            'UPDATE comments SET 
                like_count = GREATEST(0, like_count - 1),
                hot_score = GREATEST(0, like_count - 1) * 2 + reply_count
            WHERE id = ?',
            [$this->id]
        );
        // 强制重新加载模型属性
        $rawData = \Illuminate\Support\Facades\DB::table('comments')->where('id', $this->id)->first();
        $this->setRawAttributes((array) $rawData);
    }

    /**
     * 同步点赞计数（根据实际点赞记录）.
     */
    public function syncLikeCount(): void
    {
        // 获取实际的点赞记录数量
        $actualLikeCount = \App\Modules\Like\Models\Like::where('likeable_id', $this->id)
            ->where('likeable_type', 'comment')
            ->count();

        // 更新数据库中的点赞数
        \Illuminate\Support\Facades\DB::update(
            'UPDATE comments SET like_count = ? WHERE id = ?',
            [$actualLikeCount, $this->id]
        );

        // 强制重新加载模型属性
        $rawData = \Illuminate\Support\Facades\DB::table('comments')->where('id', $this->id)->first();
        $this->setRawAttributes((array) $rawData);
    }

    /**
     * 计算热门分数
     * 公式：hot_score = like_count * 2 + reply_count
     * 点赞权重更高.
     */
    public function calculateHotScore(): int
    {
        $likeCount = (int) ($this->attributes['like_count'] ?? 0);
        $replyCount = (int) ($this->attributes['reply_count'] ?? 0);

        return $likeCount * 2 + $replyCount;
    }

    /**
     * 更新热门分数.
     */
    public function updateHotScore(): void
    {
        $hotScore = $this->calculateHotScore();
        \Illuminate\Support\Facades\DB::update(
            'UPDATE comments SET hot_score = ? WHERE id = ?',
            [$hotScore, $this->id]
        );
        // 强制重新加载模型属性
        $rawData = \Illuminate\Support\Facades\DB::table('comments')->where('id', $this->id)->first();
        $this->setRawAttributes((array) $rawData);
    }

    /**
     * 增加回复计数.
     */
    public function incrementReplyCount(): void
    {
        // 合并更新 reply_count 和 hot_score，减少数据库操作
        \Illuminate\Support\Facades\DB::update(
            'UPDATE comments SET 
                reply_count = reply_count + 1,
                hot_score = like_count * 2 + (reply_count + 1)
            WHERE id = ?',
            [$this->id]
        );
        // 强制重新加载模型属性
        $rawData = \Illuminate\Support\Facades\DB::table('comments')->where('id', $this->id)->first();
        $this->setRawAttributes((array) $rawData);
    }

    /**
     * 减少回复计数.
     */
    public function decrementReplyCount(): void
    {
        // 合并更新 reply_count 和 hot_score，减少数据库操作
        \Illuminate\Support\Facades\DB::update(
            'UPDATE comments SET 
                reply_count = GREATEST(0, reply_count - 1),
                hot_score = like_count * 2 + GREATEST(0, reply_count - 1)
            WHERE id = ?',
            [$this->id]
        );
        // 强制重新加载模型属性
        $rawData = \Illuminate\Support\Facades\DB::table('comments')->where('id', $this->id)->first();
        $this->setRawAttributes((array) $rawData);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\CommentFactory::new();
    }

    // ==================== Query Scopes ====================

    /**
     * 作用域：只查询已发布的评论（兼容 STATUS_PUBLISHED 和 STATUS_NORMAL）.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->whereIn('status', [self::STATUS_PUBLISHED, self::STATUS_NORMAL]);
    }

    /**
     * 作用域：只查询待审核的评论.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * 作用域：只查询已拒绝的评论.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * 作用域：按目标筛选.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $targetType 目标类型
     * @param int                                   $targetId   目标ID
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTarget($query, string $targetType, int $targetId)
    {
        return $query->where('target_type', $targetType)
            ->where('target_id', $targetId);
    }

    /**
     * 作用域：只查询顶级评论（主评论，parent_id 为 null）.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 作用域：只查询回复（parent_id 不为 null）.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param null|int                              $parentId 父评论ID（可选）
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReplies($query, ?int $parentId = null)
    {
        $query->whereNotNull('parent_id');
        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        }

        return $query;
    }

    /**
     * 作用域：按用户筛选.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int                                  $userId 用户ID
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 作用域：按热门排序（hot_score 降序，id 降序）.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderedByHot($query)
    {
        return $query->orderBy('hot_score', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * 作用域：按最新排序（id 降序）.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderedByLatest($query)
    {
        return $query->orderBy('id', 'desc');
    }

    /**
     * 获取模块名称（用于审核配置）.
     */
    protected function getModuleName(): string
    {
        return 'comment';
    }
}
