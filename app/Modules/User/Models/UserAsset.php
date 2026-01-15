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
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAsset extends Model
{
    use SoftDeletes;

    /**
     * 资源类型：头像.
     */
    public const TYPE_AVATAR = 'avatar';

    /**
     * 资源类型：背景图.
     */
    public const TYPE_BACKGROUND = 'background';

    /**
     * 资源类型：相册.
     */
    public const TYPE_ALBUM = 'album';

    /**
     * 资源类型：其他.
     */
    public const TYPE_OTHER = 'other';

    /**
     * 审核状态：待审核.
     */
    public const REVIEW_PENDING = 'pending';

    /**
     * 审核状态：已通过.
     */
    public const REVIEW_APPROVED = 'approved';

    /**
     * 审核状态：已拒绝.
     */
    public const REVIEW_REJECTED = 'rejected';

    /**
     * 可批量赋值的属性.
     */
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'path',
        'url',
        'mime_type',
        'size',
        'width',
        'height',
        'duration',
        'thumbnail',
        'sort',
        'extra',
        'status',
        'review_status',
        'review_remark',
        'reviewer_id',
        'reviewed_at',
        'review_expires_at',
        'review_attempts',
    ];

    /**
     * 类型转换.
     */
    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'extra' => 'array',
        'reviewed_at' => 'datetime',
        'review_expires_at' => 'datetime',
        'review_attempts' => 'integer',
    ];

    /**
     * 关联用户.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 关联审核员.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * 获取资源类型列表.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_AVATAR => '头像',
            self::TYPE_BACKGROUND => '背景图',
            self::TYPE_ALBUM => '相册',
            self::TYPE_OTHER => '其他',
        ];
    }

    /**
     * 获取资源类型名称.
     */
    public function getTypeName(): string
    {
        return self::getTypes()[$this->type] ?? '未知';
    }

    /**
     * 获取资源完整URL.
     */
    public function getFullUrl(): string
    {
        return $this->url ?: asset('storage/' . $this->path);
    }

    /**
     * 获取缩略图完整URL.
     */
    public function getThumbnailUrl(): string
    {
        return $this->thumbnail ?: $this->getFullUrl();
    }

    /**
     * 获取审核状态列表.
     */
    public static function getReviewStatuses(): array
    {
        return [
            self::REVIEW_PENDING => '待审核',
            self::REVIEW_APPROVED => '已通过',
            self::REVIEW_REJECTED => '已拒绝',
        ];
    }

    /**
     * 获取审核状态名称.
     */
    public function getReviewStatusName(): string
    {
        return self::getReviewStatuses()[$this->review_status] ?? '未知';
    }

    /**
     * 检查是否待审核.
     */
    public function isPending(): bool
    {
        return $this->review_status === self::REVIEW_PENDING;
    }

    /**
     * 检查是否已通过审核.
     */
    public function isApproved(): bool
    {
        return $this->review_status === self::REVIEW_APPROVED;
    }

    /**
     * 检查是否已拒绝.
     */
    public function isRejected(): bool
    {
        return $this->review_status === self::REVIEW_REJECTED;
    }

    /**
     * 检查审核是否过期.
     */
    public function isReviewExpired(): bool
    {
        return $this->review_expires_at && $this->review_expires_at->isPast();
    }

    /**
     * 作用域：只查询待审核的资源.
     */
    public function scopePending($query)
    {
        return $query->where('review_status', self::REVIEW_PENDING);
    }

    /**
     * 作用域：只查询已通过审核的资源.
     */
    public function scopeApproved($query)
    {
        return $query->where('review_status', self::REVIEW_APPROVED);
    }

    /**
     * 作用域：只查询已拒绝的资源.
     */
    public function scopeRejected($query)
    {
        return $query->where('review_status', self::REVIEW_REJECTED);
    }

    /**
     * 作用域：只查询审核过期的资源.
     */
    public function scopeExpired($query)
    {
        return $query->where('review_expires_at', '<', now());
    }
}
