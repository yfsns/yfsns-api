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

namespace App\Modules\Post\Models;

use App\Http\Traits\IpRecordTrait;
use App\Modules\Collect\Traits\Collectable;
use App\Modules\File\Models\File;
use App\Modules\Like\Traits\Likeable;
use App\Modules\Review\Traits\HasReviewable;
use App\Modules\Share\Traits\Shareable;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Post extends Model
{
    use Collectable, HasFactory, HasReviewable, IpRecordTrait, Likeable, Shareable, SoftDeletes;

    /**
     * 状态常量.
     */
    public const STATUS_PENDING = 0; // 待审核

    public const STATUS_PUBLISHED = 1; // 已发布

    public const STATUS_REJECTED = 2; // 审核拒绝

    /**
     * 可见性：公开
     */
    public const VISIBILITY_PUBLIC = 1;

    /**
     * 可见性：粉丝可见
     */
    public const VISIBILITY_FOLLOWERS = 2;

    /**
     * 可见性：好友可见
     */
    public const VISIBILITY_FRIENDS = 3;

    /**
     * 可见性：仅自己可见
     */
    public const VISIBILITY_PRIVATE = 4;

    /**
     * 内容类型常量.
     */
    public const TYPE_POST = 'post';        // 动态

    public const TYPE_ARTICLE = 'article';  // 文章

    public const TYPE_QUESTION = 'question'; // 提问

    public const TYPE_THREAD = 'thread';    // 帖子

    public const TYPE_IMAGE = 'image';      // 图片动态（只能包含图片）

    public const TYPE_VIDEO = 'video';      // 视频动态（只能包含视频）

    /**
     * 可批量赋值属性.
     */
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'type',
        'location_id',
        'visibility',
        'status',
        'like_count',
        'collect_count',
        'comment_count',
        'share_count',
        'view_count',
        'is_top',
        'is_hot',
        'is_recommend',
        'is_essence',
        'ip',
        'ip_country',
        'ip_region',
        'ip_city',
        'ip_isp',
        'ip_location',
        'device',
        'privacy',
        'metadata',
        'repost_id',
        'repost_count',
        'published_at',
        'audited_at',
    ];

    /**
     * 类型转换.
     */
    protected $casts = [
        'images' => 'array',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'collect_count' => 'integer',
        'share_count' => 'integer',
        'status' => 'integer',
        'is_top' => 'boolean',
        'is_hot' => 'boolean',
        'is_recommend' => 'boolean',
        'is_essence' => 'boolean',
        'metadata' => 'array',
        'published_at' => 'datetime',
        'audited_at' => 'datetime',
    ];

    /**
     * 获取可见性选项.
     */
    public static function getVisibilityOptions(): array
    {
        return [
            self::VISIBILITY_PUBLIC => '公开',
            self::VISIBILITY_FOLLOWERS => '粉丝可见',
            self::VISIBILITY_FRIENDS => '好友可见',
            self::VISIBILITY_PRIVATE => '仅自己可见',
        ];
    }

    /**
     * 检查当前用户是否可以查看此帖子.
     */
    public function canViewBy(?int $userId): bool
    {
        // 自己的帖子都可以看
        if ($userId && $this->user_id === $userId) {
            return true;
        }

        // 公开帖子所有人都可以看
        if ($this->visibility === self::VISIBILITY_PUBLIC) {
            return true;
        }

        // 未登录用户只能看公开帖子
        if (! $userId) {
            return false;
        }

        // 粉丝可见
        if ($this->visibility === self::VISIBILITY_FOLLOWERS) {
            return DB::table('user_follows')
                ->where('follower_id', $userId)
                ->where('following_id', $this->user_id)
                ->exists();
        }

        // 好友可见
        if ($this->visibility === self::VISIBILITY_FRIENDS) {
            return DB::table('user_friends')
                ->where('status', 1)
                ->where(function ($query) use ($userId): void {
                    $query->where(function ($q) use ($userId): void {
                        $q->where('user_id', $userId)
                            ->where('friend_id', $this->user_id);
                    })->orWhere(function ($q) use ($userId): void {
                        $q->where('user_id', $this->user_id)
                            ->where('friend_id', $userId);
                    });
                })
                ->exists();
        }

        return false;
    }

    /**
     * 关联用户.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 关联位置.
     */
    public function location()
    {
        return $this->belongsTo(\App\Modules\Location\Models\Location::class)->withDefault();
    }

    /**
     * 获取动态的所有文件
     * 注意：文件类型使用 files.type（image/video/document/audio/other）
     * post_file.type 用于区分文件角色（cover/content/attachment），当前统一为 content.
     */
    public function files()
    {
        return $this->belongsToMany(File::class, 'post_file', 'post_id', 'file_id')
            ->withPivot('type', 'sort') // 保留 type 用于区分角色（cover/content/attachment）
            ->whereNull('files.deleted_at')
            ->orderBy('post_file.sort', 'asc')
            ->withTimestamps(); // 自动写入中间表时间戳
    }

    /**
     * 获取动态的图片
     * 使用 files.type 字段过滤，而不是 post_file.type（post_file.type 用于区分角色）.
     */
    public function images()
    {
        return $this->belongsToMany(File::class, 'post_file', 'post_id', 'file_id')
            ->withPivot('type', 'sort')
            ->where('files.type', 'image') // 使用 files.type 而不是 mime_type
            ->whereNull('files.deleted_at')
            ->orderBy('post_file.sort', 'asc')
            ->withTimestamps();
    }

    /**
     * 获取动态的视频
     * 使用 files.type 字段过滤，而不是 post_file.type（post_file.type 用于区分角色）.
     */
    public function videos()
    {
        return $this->belongsToMany(File::class, 'post_file', 'post_id', 'file_id')
            ->withPivot('type', 'sort')
            ->where('files.type', 'video') // 使用 files.type 而不是 mime_type
            ->whereNull('files.deleted_at')
            ->orderBy('post_file.sort', 'asc')
            ->withTimestamps();
    }

    /**
     * 获取动态的附件.
     */
    public function attachments()
    {
        return $this->belongsToMany(File::class, 'post_file')
            ->wherePivot('type', 'attachment')
            ->withPivot('sort')
            ->withTimestamps();
    }

    /**
     * 关联评论（使用多态关联）.
     */
    public function comments()
    {
        return $this->hasMany(\App\Modules\Comment\Models\Comment::class, 'target_id')
            ->where('target_type', 'post');
    }


    /**
     * 获取原动态
     */
    public function originalPost()
    {
        return $this->belongsTo(self::class, 'repost_id');
    }

    /**
     * 获取转发记录.
     */
    public function reposts()
    {
        return $this->hasMany(self::class, 'repost_id');
    }

    /**
     * 增加转发计数.
     */
    public function incrementRepostCount(): void
    {
        $this->increment('repost_count');
    }

    /**
     * 减少转发计数.
     */
    public function decrementRepostCount(): void
    {
        $this->decrement('repost_count');
    }

    /**
     * 关联@用户.
     */
    public function mentions()
    {
        return $this->hasMany(\App\Modules\User\Models\UserMention::class, 'content_id')
            ->where('content_type', \App\Modules\User\Models\UserMention::TYPE_POST);
    }

    /**
     * 关联话题标签.
     */
    public function topics()
    {
        return $this->belongsToMany(\App\Modules\Topic\Models\Topic::class, 'post_topics', 'post_id', 'topic_id')
            ->withPivot('position')
            ->withTimestamps();
    }


    // ==================== Query Scopes ====================

    /**
     * 作用域：只查询已发布的动态.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * 作用域：只查询待审核的动态.
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
     * 作用域：只查询已拒绝的动态.
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
     * 作用域：按类型筛选.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $type 内容类型
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
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
     * 作用域：只查询公开的动态.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    /**
     * 作用域：应用标准排序（置顶、精华、推荐、时间）.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_top', 'desc')
            ->orderBy('is_essence', 'desc')
            ->orderBy('is_recommend', 'desc')
            ->orderBy('created_at', 'desc');
    }

    // ==================== Accessors ====================

    /**
     * 获取位置信息（Accessor）- 保持向后兼容性.
     */
    public function getLocationAttribute(): ?array
    {
        // 检查关联是否已加载
        if (!$this->relationLoaded('location')) {
            return null;
        }

        $location = $this->getRelation('location');

        // 如果是数组（Laravel 某些版本的行为），转换为对象
        if (is_array($location)) {
            $location = (object) $location;
        }

        // 如果没有位置数据，返回 null
        if (!$location) {
            return null;
        }

        return [
            'title' => $location->title ?? null,
            'latitude' => isset($location->latitude) ? (float) $location->latitude : null,
            'longitude' => isset($location->longitude) ? (float) $location->longitude : null,
            'address' => $location->address ?? null,
            'country' => $location->country ?? null,
            'province' => $location->province ?? null,
            'city' => $location->city ?? null,
            'district' => $location->district ?? null,
            'place_name' => $location->place_name ?? null,
            'category' => $location->category ?? null,
        ];
    }

    /**
     * 获取状态文本（Accessor）.
     *
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        return match ((int) $this->status) {
            self::STATUS_PENDING => '待审核',
            self::STATUS_PUBLISHED => '已发布',
            self::STATUS_REJECTED => '已拒绝',
            default => '未知',
        };
    }

    /**
     * 获取可见性文本（Accessor）.
     *
     * @return string
     */
    public function getVisibilityTextAttribute(): string
    {
        return match ((int) $this->visibility) {
            self::VISIBILITY_PUBLIC => '公开',
            self::VISIBILITY_FOLLOWERS => '粉丝可见',
            self::VISIBILITY_FRIENDS => '好友可见',
            self::VISIBILITY_PRIVATE => '仅自己可见',
            default => '未知',
        };
    }

    /**
     * 获取HTML渲染的内容（Accessor）.
     * 动态生成包含@用户和#话题超链接的HTML内容
     *
     * @return string
     */
    public function getContentHtmlAttribute(): string
    {
        $content = $this->content;
        if (empty($content)) {
            return '';
        }

        // 获取@提及的用户（只查询必要的字段）
        $mentions = $this->mentions()
            ->select('receiver_id', 'username', 'nickname_at_time')
            ->get()
            ->keyBy('username');

        // 获取话题（只查询必要的字段）
        $topics = $this->topics()
            ->select('topics.id', 'topics.name')
            ->get()
            ->keyBy('name');

        // 处理@用户提及
        foreach ($mentions as $mention) {
            $userId = $mention->receiver_id;
            $nickname = htmlspecialchars($mention->nickname_at_time, ENT_QUOTES, 'UTF-8');
            // 用昵称匹配内容中的@提及，因为内容显示的是昵称
            // 使用前瞻和后顾来确保只匹配完整的@用户名
            $pattern = '/@' . preg_quote($mention->nickname_at_time, '/') . '(?=\s|$|[^a-zA-Z0-9\x{4e00}-\x{9fff}])/u';
            $replacement = "<a href=\"/profile/{$userId}\" class=\"mention-link\" target=\"_blank\">@{$nickname}</a>";
            $content = preg_replace($pattern, $replacement, $content);
        }

        // 处理#话题
        foreach ($topics as $topic) {
            $topicId = $topic->id;
            $topicName = $topic->name;
            $safeName = htmlspecialchars($topicName, ENT_QUOTES, 'UTF-8');
            // 匹配完整的 #话题名# 格式
            $pattern = '/#' . preg_quote($topicName, '/') . '#(?=\s|$)/u';
            $replacement = "<a href=\"/topic/{$topicId}\" class=\"topic-link\" target=\"_blank\">#{$safeName}#</a>";
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    /**
     * 获取封面文件
     */
    public function cover()
    {
        return $this->files()->wherePivot('type', 'cover')->first();
    }

    /**
     * 获取内容文件（排除封面）
     */
    public function contentFiles()
    {
        return $this->files()->wherePivot('type', 'content');
    }

    /**
     * 获取所有文件按类型分组
     */
    public function getFilesGrouped()
    {
        return [
            'cover' => $this->cover(),
            'content' => $this->contentFiles()->get(),
        ];
    }

    /**
     * 获取模块名称（用于审核配置）.
     */
    protected function getModuleName(): string
    {
        return 'post';
    }
}
