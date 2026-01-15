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

namespace App\Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 积分记录模型.
 *
 * 主要功能：
 * 1. 记录用户积分变动详情
 * 2. 关联积分规则
 * 3. 提供积分历史查询
 */
class PointsRecord extends Model
{
    /**
     * 指定表名，对应迁移文件中的 wallet_point_records 表
     */
    protected $table = 'wallet_point_records';

    /**
     * 可批量赋值的属性
     * 注意：字段名与迁移文件保持一致.
     */
    protected $fillable = [
        'user_id',        // 用户ID
        'points_rule_id', // 积分规则ID
        'type',           // 类型：earn, use, expire, adjust
        'amount',         // 积分变动数量（正数为增加，负数为减少）
        'description',    // 描述
        'metadata',       // 元数据（JSON格式）
    ];

    /**
     * 属性类型转换.
     */
    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'json',
    ];

    /**
     * 关联用户.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class);
    }

    /**
     * 关联积分规则.
     */
    public function pointsRule(): BelongsTo
    {
        return $this->belongsTo(PointsRule::class);
    }

    /**
     * 获取 points 访问器（兼容旧代码）
     * 实际字段是 amount.
     */
    public function getPointsAttribute()
    {
        return $this->attributes['amount'] ?? 0;
    }

    /**
     * 设置 points 访问器（兼容旧代码）.
     */
    public function setPointsAttribute($value): void
    {
        $this->attributes['amount'] = $value;
    }

    /**
     * 获取积分变动绝对值
     */
    public function getAbsolutePointsAttribute(): int
    {
        return abs($this->amount);
    }
}
