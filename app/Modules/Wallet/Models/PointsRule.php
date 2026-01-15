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

use App\Modules\Comment\Models\Comment;
use App\Modules\Post\Models\Post;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 积分规则模型.
 *
 * 主要功能：
 * 1. 定义各种积分获取规则
 * 2. 支持多种触发条件
 * 3. 灵活的积分计算方式
 */
class PointsRule extends Model
{
    /**
     * 可批量赋值的属性.
     */
    protected $fillable = [
        'name',           // 规则名称
        'code',           // 规则代码（唯一标识）
        'description',    // 规则描述
        'trigger_type',   // 触发类型：daily(每日)、once(一次性)、action(动作触发)
        'action',         // 触发动作：post_create、comment_create、login、share等
        'points',         // 积分数量
        'points_type',    // 积分类型：fixed(固定)、dynamic(动态计算)
        'formula',        // 动态计算公式（JSON格式）
        'max_times',      // 最大触发次数（0表示无限制）
        'daily_limit',    // 每日限制次数
        'conditions',     // 触发条件（JSON格式）
        'status',         // 状态：active(激活)、inactive(未激活)
        'start_time',     // 开始时间
        'end_time',       // 结束时间
        'priority',        // 优先级（数字越大优先级越高）
    ];

    /**
     * 属性类型转换.
     */
    protected $casts = [
        'points' => 'integer',
        'max_times' => 'integer',
        'daily_limit' => 'integer',
        'formula' => 'json',
        'conditions' => 'json',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'priority' => 'integer',
    ];

    /**
     * 关联积分记录.
     */
    public function pointsRecords(): HasMany
    {
        return $this->hasMany(PointsRecord::class);
    }

    /**
     * 检查规则是否可用.
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && now()->between($this->start_time, $this->end_time);
    }

    /**
     * 检查用户是否满足触发条件.
     */
    public function checkConditions(int $userId, array $context = []): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        // 检查触发动作
        if (isset($context['action']) && $context['action'] !== $this->action) {
            return false;
        }

        // 检查触发次数限制
        if ($this->max_times > 0) {
            $usedTimes = $this->pointsRecords()
                ->where('user_id', $userId)
                ->count();
            if ($usedTimes >= $this->max_times) {
                return false;
            }
        }

        // 检查每日限制
        if ($this->daily_limit > 0) {
            $todayUsed = $this->pointsRecords()
                ->where('user_id', $userId)
                ->whereDate('created_at', today())
                ->count();
            if ($todayUsed >= $this->daily_limit) {
                return false;
            }
        }

        // 检查自定义条件
        if ($this->conditions) {
            return $this->evaluateConditions($userId, $context);
        }

        return true;
    }

    /**
     * 计算积分数量.
     */
    public function calculatePoints(array $context = []): int
    {
        if ($this->points_type === 'fixed') {
            return $this->points;
        }

        if ($this->points_type === 'dynamic' && $this->formula) {
            return $this->evaluateFormula($context);
        }

        return 0;
    }

    /**
     * 获取活跃的规则.
     */
    public static function getActiveRules(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('status', 'active')
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * 评估自定义条件.
     */
    protected function evaluateConditions(int $userId, array $context): bool
    {
        $conditions = $this->conditions;

        foreach ($conditions as $condition) {
            $result = $this->evaluateSingleCondition($userId, $condition, $context);
            if (! $result) {
                return false;
            }
        }

        return true;
    }

    /**
     * 评估单个条件.
     */
    protected function evaluateSingleCondition(int $userId, array $condition, array $context): bool
    {
        $type = $condition['type'] ?? '';
        $value = $condition['value'] ?? '';
        $operator = $condition['operator'] ?? '=';

        switch ($type) {
            case 'user_level':
                $userLevel = User::find($userId)->level ?? 0;

                return $this->compare($userLevel, $operator, $value);

            case 'post_count':
                $postCount = Post::where('user_id', $userId)->count();

                return $this->compare($postCount, $operator, $value);

            case 'comment_count':
                $commentCount = Comment::where('user_id', $userId)->count();

                return $this->compare($commentCount, $operator, $value);

            case 'login_days':
                // 简化处理：使用用户创建天数作为登录天数
                $user = User::find($userId);
                $loginDays = $user ? $user->created_at->diffInDays(now()) : 0;

                return $this->compare($loginDays, $operator, $value);

            default:
                return true;
        }
    }

    /**
     * 评估动态公式.
     */
    protected function evaluateFormula(array $context): int
    {
        $formula = $this->formula;
        $basePoints = $formula['base'] ?? 0;
        $multiplier = $formula['multiplier'] ?? 1;
        $bonus = $formula['bonus'] ?? 0;

        $calculatedPoints = $basePoints * $multiplier + $bonus;

        // 应用上下文变量
        if (isset($context['amount'])) {
            $calculatedPoints = $calculatedPoints * $context['amount'];
        }

        return max(0, (int) $calculatedPoints);
    }

    /**
     * 比较操作.
     */
    protected function compare($a, string $operator, $b): bool
    {
        switch ($operator) {
            case '=': return $a == $b;
            case '!=': return $a != $b;
            case '>': return $a > $b;
            case '>=': return $a >= $b;
            case '<': return $a < $b;
            case '<=': return $a <= $b;
            default: return false;
        }
    }
}
