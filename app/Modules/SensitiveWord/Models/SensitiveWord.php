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

namespace App\Modules\SensitiveWord\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SensitiveWord extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * 分类常量.
     */
    public const CATEGORY_POLITICAL = 'political';     // 政治敏感

    public const CATEGORY_PORNOGRAPHIC = 'pornographic'; // 色情低俗

    public const CATEGORY_VIOLENCE = 'violence';       // 暴力血腥

    public const CATEGORY_ADVERTISING = 'advertising';  // 广告营销

    public const CATEGORY_ILLEGAL = 'illegal';         // 违法违规

    public const CATEGORY_OTHER = 'other';             // 其他

    /**
     * 级别常量.
     */
    public const LEVEL_LOW = 'low';        // 低

    public const LEVEL_MEDIUM = 'medium';  // 中

    public const LEVEL_HIGH = 'high';      // 高

    /**
     * 处理方式常量.
     */
    public const ACTION_REPLACE = 'replace';  // 替换为***

    public const ACTION_REJECT = 'reject';    // 拒绝发布

    public const ACTION_REVIEW = 'review';    // 标记待审核

    protected $fillable = [
        'word',
        'category',
        'level',
        'action',
        'replacement',
        'is_regex',
        'hit_count',
        'status',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_regex' => 'boolean',
        'hit_count' => 'integer',
        'status' => 'boolean',
    ];

    /**
     * 获取分类列表.
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_POLITICAL => '政治敏感',
            self::CATEGORY_PORNOGRAPHIC => '色情低俗',
            self::CATEGORY_VIOLENCE => '暴力血腥',
            self::CATEGORY_ADVERTISING => '广告营销',
            self::CATEGORY_ILLEGAL => '违法违规',
            self::CATEGORY_OTHER => '其他',
        ];
    }

    /**
     * 获取级别列表.
     */
    public static function getLevels(): array
    {
        return [
            self::LEVEL_LOW => '低',
            self::LEVEL_MEDIUM => '中',
            self::LEVEL_HIGH => '高',
        ];
    }

    /**
     * 获取处理方式列表.
     */
    public static function getActions(): array
    {
        return [
            self::ACTION_REPLACE => '替换为***',
            self::ACTION_REJECT => '拒绝发布',
            self::ACTION_REVIEW => '标记待审核',
        ];
    }

    /**
     * 命中日志关系.
     */
    public function logs()
    {
        return $this->hasMany(SensitiveWordLog::class);
    }

    /**
     * 增加命中次数.
     */
    public function incrementHitCount(): void
    {
        $this->increment('hit_count');
    }
}
