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
use Illuminate\Database\Eloquent\Builder;

class UserMention extends Model
{
    protected $table = 'user_mentions';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content_type',
        'content_id',
        'username',
        'nickname_at_time',
        'position',
        'status',
        'metadata',
        'read_at',
    ];

    protected $casts = [
        'position' => 'integer',
        'metadata' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 状态常量
     */
    public const STATUS_UNREAD = 'unread';
    public const STATUS_READ = 'read';

    /**
     * 内容类型常量
     */
    public const TYPE_POST = 'post';
    public const TYPE_COMMENT = 'comment';
    public const TYPE_MESSAGE = 'message';

    /**
     * 关联发送者
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * 关联接收者
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * 获取内容类型的显示名称
     */
    public static function getContentTypeNames(): array
    {
        return [
            self::TYPE_POST => '动态',
            self::TYPE_COMMENT => '评论',
            self::TYPE_MESSAGE => '私信',
        ];
    }

    /**
     * 获取状态的显示名称
     */
    public static function getStatusNames(): array
    {
        return [
            self::STATUS_UNREAD => '未读',
            self::STATUS_READ => '已读',
        ];
    }

    /**
     * 标记为已读
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'status' => self::STATUS_READ,
            'read_at' => now(),
        ]);
    }

    /**
     * 批量标记为已读
     */
    public static function markAsReadBulk(array $ids, int $userId): int
    {
        return self::whereIn('id', $ids)
            ->where('receiver_id', $userId)
            ->where('status', self::STATUS_UNREAD)
            ->update([
                'status' => self::STATUS_READ,
                'read_at' => now(),
            ]);
    }

    /**
     * 检查是否已读
     */
    public function isRead(): bool
    {
        return $this->status === self::STATUS_READ;
    }

    // ==================== Query Scopes ====================

    /**
     * 作用域：只查询未读的@
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_UNREAD);
    }

    /**
     * 作用域：只查询已读的@
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_READ);
    }

    /**
     * 作用域：按接收者筛选
     */
    public function scopeByReceiver(Builder $query, int $userId): Builder
    {
        return $query->where('receiver_id', $userId);
    }

    /**
     * 作用域：按发送者筛选
     */
    public function scopeBySender(Builder $query, int $userId): Builder
    {
        return $query->where('sender_id', $userId);
    }

    /**
     * 作用域：按内容类型筛选
     */
    public function scopeByContentType(Builder $query, string $contentType): Builder
    {
        return $query->where('content_type', $contentType);
    }

    /**
     * 作用域：按内容ID筛选
     */
    public function scopeByContentId(Builder $query, int $contentId): Builder
    {
        return $query->where('content_id', $contentId);
    }

    /**
     * 作用域：获取最新的@
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    // ==================== Accessors ====================

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute(): string
    {
        return self::getStatusNames()[$this->status] ?? '未知';
    }

    /**
     * 获取内容类型文本
     */
    public function getContentTypeTextAttribute(): string
    {
        return self::getContentTypeNames()[$this->content_type] ?? '未知';
    }
}
