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

namespace Plugins\TencentSmsPlugin\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 腾讯云短信模板模型
 */
class TencentSmsTemplate extends Model
{
    protected $table = 'tencent_sms_templates';

    protected $fillable = [
        'template_id',
        'template_name',
        'template_content',
        'audit_status',
        'international',
        'status',
        'platform_data'
    ];

    protected $casts = [
        'international' => 'boolean',
        'status' => 'boolean',
        'platform_data' => 'array'
    ];

    /**
     * 获取审核状态标签
     */
    public function getAuditStatusLabelAttribute(): string
    {
        return match($this->audit_status) {
            'pending' => '待审核',
            'approved' => '已通过',
            'rejected' => '已拒绝',
            default => '未知'
        };
    }

    /**
     * 获取状态标签
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status ? '启用' : '禁用';
    }

    /**
     * 作用域：已审核通过的模板
     */
    public function scopeApproved($query)
    {
        return $query->where('audit_status', 'approved');
    }

    /**
     * 作用域：启用的模板
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', 1);
    }

    /**
     * 作用域：国内模板
     */
    public function scopeDomestic($query)
    {
        return $query->where('international', false);
    }

    /**
     * 作用域：国际模板
     */
    public function scopeInternational($query)
    {
        return $query->where('international', true);
    }
}
