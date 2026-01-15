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

namespace App\Modules\Search\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    /**
     * 搜索状态常量.
     */
    public const STATUS_SUCCESS = 'success';

    public const STATUS_ERROR = 'error';

    public const STATUS_TIMEOUT = 'timeout';

    protected $fillable = [
        'query',
        'type',
        'filters',
        'results_count',
        'ip_address',
        'user_agent',
        'user_id',
        'response_time',
        'status',
        'error_message',
    ];

    protected $casts = [
        'filters' => 'array',
        'results_count' => 'integer',
        'response_time' => 'integer',
    ];

    /**
     * 获取状态列表.
     */
    public static function getStatusList(): array
    {
        return [
            self::STATUS_SUCCESS => '成功',
            self::STATUS_ERROR => '错误',
            self::STATUS_TIMEOUT => '超时',
        ];
    }

    /**
     * 用户关系.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 记录搜索日志.
     */
    public static function logSearch(array $data): self
    {
        return self::create([
            'query' => $data['query'] ?? '',
            'type' => $data['type'] ?? null,
            'filters' => $data['filters'] ?? null,
            'results_count' => $data['results_count'] ?? 0,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'user_id' => $data['user_id'] ?? auth()->id(),
            'response_time' => $data['response_time'] ?? null,
            'status' => $data['status'] ?? self::STATUS_SUCCESS,
            'error_message' => $data['error_message'] ?? null,
        ]);
    }

    /**
     * 获取热门搜索词.
     */
    public static function getPopularKeywords(int $limit = 20): array
    {
        return self::selectRaw('query, COUNT(*) as count')
            ->where('status', self::STATUS_SUCCESS)
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('query')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->pluck('count', 'query')
            ->toArray();
    }

    /**
     * 获取搜索统计
     */
    public static function getSearchStats(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        return [
            'total_searches' => self::count(),
            'today_searches' => self::where('created_at', '>=', $today)->count(),
            'this_month_searches' => self::where('created_at', '>=', $thisMonth)->count(),
            'success_rate' => self::where('status', self::STATUS_SUCCESS)->count() / max(self::count(), 1) * 100,
            'avg_response_time' => self::where('status', self::STATUS_SUCCESS)->avg('response_time'),
        ];
    }
}
