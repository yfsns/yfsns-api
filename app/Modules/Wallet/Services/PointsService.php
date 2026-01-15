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

namespace App\Modules\Wallet\Services;

use App\Modules\Wallet\Models\PointsRecord;
use App\Modules\Wallet\Models\PointsRule;

use function count;

use Illuminate\Support\Facades\DB;

/**
 * 积分服务类
 *
 * 处理积分相关的所有业务逻辑，包括自动发放、使用、统计等功能
 */
class PointsService
{
    /**
     * 触发积分规则.
     *
     * @param int    $userId  用户ID
     * @param string $action  触发动作
     * @param array  $context 上下文信息
     *
     * @return array 发放结果
     */
    public function triggerPointsRules(int $userId, string $action, array $context = []): array
    {
        $results = [];
        $activeRules = PointsRule::getActiveRules();

        foreach ($activeRules as $rule) {
            if ($rule->action === $action && $rule->checkConditions($userId, $context)) {
                $points = $rule->calculatePoints($context);
                $result = $this->addPoints($userId, $points, $rule, $action, $context);
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * 添加积分.
     *
     * @param int             $userId  用户ID
     * @param int             $points  积分数量
     * @param null|PointsRule $rule    积分规则
     * @param string          $action  触发动作
     * @param array           $context 上下文信息
     *
     * @return array 操作结果
     */
    public function addPoints(int $userId, int $points, ?PointsRule $rule = null, string $action = '', array $context = []): array
    {
        if ($points <= 0) {
            return [
                'success' => false,
                'error' => '积分数量必须大于0',
            ];
        }

        DB::transaction(function () use ($userId, $points, $rule, $context): void {
            // 创建积分记录（使用 amount 和 type 字段）
            PointsRecord::create([
                'user_id' => $userId,
                'points_rule_id' => $rule?->id,
                'amount' => $points,  // 使用 amount 字段
                'type' => 'earn',     // 使用 type 字段
                'metadata' => $context,  // 使用 metadata 字段
                'description' => $rule?->description ?? '积分变动',
            ]);
        });

        return [
            'success' => true,
            'points' => $points,
            'rule_id' => $rule?->id,
            'rule_name' => $rule?->name,
            'message' => "成功获得 {$points} 积分",
        ];
    }

    /**
     * 使用积分.
     *
     * @param int    $userId      用户ID
     * @param int    $points      积分数量
     * @param string $description 描述
     * @param array  $context     上下文信息
     *
     * @return array 操作结果
     */
    public function usePoints(int $userId, int $points, string $description = '使用积分', array $context = []): array
    {
        if ($points <= 0) {
            return [
                'success' => false,
                'error' => '积分数量必须大于0',
            ];
        }

        // 检查积分是否足够
        if (! $this->hasEnoughPoints($userId, $points)) {
            return [
                'success' => false,
                'error' => '积分不足',
            ];
        }

        DB::transaction(function () use ($userId, $points, $description, $context): void {
            // 创建积分记录（负数表示消费，使用 amount 和 type 字段）
            PointsRecord::create([
                'user_id' => $userId,
                'points_rule_id' => null,
                'amount' => -$points,  // 使用 amount 字段
                'type' => 'use',       // 使用 type 字段
                'metadata' => $context,  // 使用 metadata 字段
                'description' => $description,
            ]);
        });

        return [
            'success' => true,
            'points' => $points,
            'message' => "成功使用 {$points} 积分",
        ];
    }

    /**
     * 获取用户积分统计
     *
     * @param int $userId 用户ID
     *
     * @return array 统计信息
     */
    public function getUserPointsStats(int $userId): array
    {
        // 使用 amount 字段（迁移文件中的实际字段名）
        $totalEarned = PointsRecord::where('user_id', $userId)
            ->where('amount', '>', 0)
            ->sum('amount');

        $totalUsed = abs(PointsRecord::where('user_id', $userId)
            ->where('amount', '<', 0)
            ->sum('amount'));

        $currentPoints = $totalEarned - $totalUsed;

        $todayEarned = PointsRecord::where('user_id', $userId)
            ->where('amount', '>', 0)
            ->whereDate('created_at', today())
            ->sum('amount');

        $monthlyEarned = PointsRecord::where('user_id', $userId)
            ->where('amount', '>', 0)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        return [
            'current_points' => $currentPoints,
            'total_earned' => $totalEarned,
            'total_used' => $totalUsed,
            'today_earned' => $todayEarned,
            'monthly_earned' => $monthlyEarned,
            'available_points' => $currentPoints,
        ];
    }

    /**
     * 获取积分排行榜.
     *
     * @param int $limit 限制数量
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPointsLeaderboard(int $limit = 20)
    {
        // 使用 amount 字段（实际数据库字段名）
        return PointsRecord::select('user_id', DB::raw('SUM(amount) as total_points'))
            ->with('user:' . \App\Modules\User\Models\User::BASIC_FIELDS)
            ->groupBy('user_id')
            ->having('total_points', '>', 0)
            ->orderBy('total_points', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 批量发放积分.
     *
     * @param array  $userIds     用户ID数组
     * @param int    $points      积分数量
     * @param string $description 描述
     *
     * @return array 发放结果
     */
    public function batchAddPoints(array $userIds, int $points, string $description = '批量发放积分'): array
    {
        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($userIds as $userId) {
            $result = $this->addPoints($userId, $points, null, 'batch_add', [
                'batch_id' => uniqid(),
                'description' => $description,
            ]);

            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }

            $results[] = $result;
        }

        return [
            'total' => count($userIds),
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'results' => $results,
        ];
    }

    /**
     * 检查积分是否足够
     *
     * @param int $userId         用户ID
     * @param int $requiredPoints 需要的积分
     */
    public function hasEnoughPoints(int $userId, int $requiredPoints): bool
    {
        // 使用 amount 字段
        $totalEarned = PointsRecord::where('user_id', $userId)
            ->where('amount', '>', 0)
            ->sum('amount');

        $totalUsed = abs(PointsRecord::where('user_id', $userId)
            ->where('amount', '<', 0)
            ->sum('amount'));

        $availablePoints = $totalEarned - $totalUsed;

        return $availablePoints >= $requiredPoints;
    }

    /**
     * 获取用户积分历史.
     *
     * @param int $userId 用户ID
     * @param int $limit  限制数量
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserPointsHistory(int $userId, int $limit = 50)
    {
        return PointsRecord::with('pointsRule')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
