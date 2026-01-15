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

namespace App\Modules\Review\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 统一审核日志模型.
 *
 * 支持多态关联，可以审核不同类型的内容（article、post、thread等）
 */
class ReviewLog extends Model
{
    /**
     * 审核渠道常量.
     */
    public const CHANNEL_MANUAL = 'manual';  // 人工审核

    public const CHANNEL_AI = 'ai';          // AI审核

    protected $table = 'review_logs';

    protected $fillable = [
        'reviewable_type',      // 被审核内容的类型（Article、Post、Thread等）
        'reviewable_id',        // 被审核内容的ID
        'channel',              // 审核渠道：manual=人工, ai=AI
        'admin_id',             // 管理员ID（人工审核时使用）
        'plugin_name',          // 插件名称（AI审核时使用）
        'previous_status',      // 审核前状态
        'new_status',           // 审核后状态
        'remark',               // 审核备注/原因
        'audit_result',         // 审核结果详情（JSON，AI审核时使用）
        'extra_data',           // 扩展数据（JSON，各模块自定义参数）
    ];

    protected $casts = [
        'audit_result' => 'array',
        'extra_data' => 'array',    // 扩展数据，各模块自定义参数
    ];

    /**
     * 多态关联：被审核的内容.
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 审核管理员.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class, 'admin_id');
    }

    /**
     * 获取审核渠道列表.
     */
    public static function getChannelList(): array
    {
        return [
            self::CHANNEL_MANUAL => '人工审核',
            self::CHANNEL_AI => 'AI审核',
        ];
    }

    // ==================== 查询作用域 ====================


    /**
     * 人工审核查询作用域.
     */
    public function scopeManual($query)
    {
        return $query->where('channel', self::CHANNEL_MANUAL);
    }

    /**
     * AI审核查询作用域.
     */
    public function scopeAi($query)
    {
        return $query->where('channel', self::CHANNEL_AI);
    }

    /**
     * 审核通过查询作用域.
     */
    public function scopeApproved($query)
    {
        return $query->where(function ($q) {
            // 支持字符串和数字状态
            $q->where('new_status', 'published')
              ->orWhere('new_status', '1');
        });
    }

    /**
     * 审核拒绝查询作用域.
     */
    public function scopeRejected($query)
    {
        return $query->where(function ($q) {
            // 支持字符串和数字状态
            $q->where('new_status', 'rejected')
              ->orWhere('new_status', '2');
        });
    }

    /**
     * 按时间范围查询.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * 按管理员查询.
     */
    public function scopeByAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * 按内容类型查询.
     */
    public function scopeByContentType($query, $contentType)
    {
        return $query->where('reviewable_type', $contentType);
    }

    // ==================== 工具方法 ====================

    /**
     * 是否为审核通过.
     */
    public function isApproved(): bool
    {
        return in_array($this->new_status, ['published', '1']);
    }

    /**
     * 是否为审核拒绝.
     */
    public function isRejected(): bool
    {
        return in_array($this->new_status, ['rejected', '2']);
    }

    /**
     * 获取审核结果描述.
     */
    public function getResultDescription(): string
    {
        if ($this->isApproved()) {
            return '审核通过';
        }

        if ($this->isRejected()) {
            return '审核拒绝';
        }

        return '其他状态';
    }

    /**
     * 获取状态变化描述.
     */
    public function getStatusChangeDescription(): string
    {
        return "从 '{$this->previous_status}' 变更为 '{$this->new_status}'";
    }

    /**
     * 获取审核渠道描述.
     */
    public function getChannelDescription(): string
    {
        return self::getChannelList()[$this->channel] ?? '未知渠道';
    }
}
