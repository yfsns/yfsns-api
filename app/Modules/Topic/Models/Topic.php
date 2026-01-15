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

namespace App\Modules\Topic\Models;

use App\Modules\Review\Traits\HasReviewable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Topic extends Model
{
    use HasFactory, HasReviewable, SoftDeletes;

    /**
     * 状态常量（与 Post/Comment 保持一致）.
     */
    public const STATUS_PENDING = 0; // 待审核

    public const STATUS_PUBLISHED = 1; // 已发布（审核通过/启用）

    public const STATUS_REJECTED = 2; // 审核拒绝（禁用）

    // 兼容旧的状态值（向后兼容）
    public const STATUS_ACTIVE = 1; // 启用（等同于 STATUS_PUBLISHED）

    public const STATUS_DISABLED = 2; // 禁用（等同于 STATUS_REJECTED）

    protected $table = 'topics';

    /**
     * 可批量赋值的属性.
     */
    protected $fillable = [
        'name',
        'description',
        'cover',
        'post_count',
        'follower_count',
        'status',
        'created_by',    // 添加审计字段
        'updated_by',
        'deleted_by',
    ];

    /**
     * 应该被转换为原生类型的属性.
     */
    protected $casts = [
        'status' => 'integer',
        'post_count' => 'integer',
        'follower_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 获取所有可用的状态
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'pending',
            self::STATUS_ACTIVE => 'active',
            self::STATUS_DISABLED => 'disabled',
        ];
    }

    /**
     * 获取状态文本.
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => '待审核',
            self::STATUS_PUBLISHED, self::STATUS_ACTIVE => '已发布',
            self::STATUS_REJECTED, self::STATUS_DISABLED => '已拒绝',
            default => '未知',
        };
    }

    /**
     * 创建者关联.
     */
    public function creator()
    {
        return $this->belongsTo('App\Modules\User\Models\User', 'created_by');
    }

    /**
     * 更新者关联.
     */
    public function updater()
    {
        return $this->belongsTo('App\Modules\User\Models\User', 'updated_by');
    }

    /**
     * 删除者关联.
     */
    public function deleter()
    {
        return $this->belongsTo('App\Modules\User\Models\User', 'deleted_by');
    }

    /**
     * 关联的动态
     */
    public function posts()
    {
        return $this->belongsToMany(
            'App\Modules\Post\Models\Post',
            'post_topics',
            'topic_id',
            'post_id'
        )->withTimestamps();
    }

    /**
     * 获取封面URL.
     */
    public function getCoverUrlAttribute(): ?string
    {
        if (! $this->cover) {
            return null;
        }

        // 如果已经是完整URL，直接返回
        if (str_starts_with($this->cover, 'http://') || str_starts_with($this->cover, 'https://')) {
            return $this->cover;
        }

        // 否则拼接存储路径
        return config('app.url') . '/storage/' . $this->cover;
    }

    /**
     * 自动填充审计字段.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model): void {
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model): void {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::deleting(function ($model): void {
            if (auth()->check()) {
                $model->deleted_by = auth()->id();
            }
        });
    }

    /**
     * 获取模块名称（用于审核配置）.
     */
    protected function getModuleName(): string
    {
        return 'topic';
    }
}
