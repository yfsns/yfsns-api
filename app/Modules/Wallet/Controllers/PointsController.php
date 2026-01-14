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

namespace App\Modules\Wallet\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Wallet\Models\PointsRecord;
use App\Modules\Wallet\Models\PointsRule;
use App\Modules\Wallet\Requests\PointsRequest;
use App\Modules\Wallet\Resources\PointsResource;
use App\Modules\Wallet\Services\PointsService;

use function count;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 积分模块
 *
 * 积分控制器
 *
 * 主要功能：
 * 1. 提供积分相关的API接口
 * 2. 处理积分的获取、使用、查询等操作
 * 3. 管理积分规则和记录
 * 4. 所有返回数据使用Resource格式化为驼峰格式
 */
class PointsController extends Controller
{
    /**
     * @var PointsService
     */
    protected $pointsService;

    /**
     * 构造函数.
     */
    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /**
     * 获取用户积分统计
     *
     * @authenticated
     */
    public function getStats(Request $request): JsonResponse
    {
        $stats = $this->pointsService->getUserPointsStats($request->user()->id);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => PointsResource::stats($stats),
        ], 200);
    }

    /**
     * 获取积分历史记录.
     *
     * @authenticated
     */
    public function getHistory(PointsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $limit = $data['limit'] ?? 20;
        $history = $this->pointsService->getUserPointsHistory($request->user()->id, $limit);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => PointsResource::collection($history),
                'total' => $history->count(),
                'limit' => $limit,
            ],
        ], 200);
    }

    /**
     * 获取积分排行榜.
     *
     * @authenticated
     */
    public function getLeaderboard(GetPointsLeaderboardRequest $request): JsonResponse
    {
        $data = $request->validated();

        $limit = $data['limit'] ?? 20;
        $leaderboard = $this->pointsService->getPointsLeaderboard($limit);

        // 格式化排行榜数据为驼峰格式
        $formattedData = $leaderboard->map(function ($item) {
            return [
                'userId' => (string) $item->user_id,
                'totalPoints' => $item->total_points,
                'user' => [
                    'id' => (string) $item->user->id,
                    'username' => $item->user->username,
                    'nickname' => $item->user->nickname,
                    'avatarUrl' => $item->user->avatar_url,
                ],
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => $formattedData,
                'total' => $leaderboard->count(),
                'limit' => $limit,
            ],
        ], 200);
    }

    /**
     * 手动添加积分（管理员功能）.
     *
     * @authenticated
     */
    public function addPoints(PointsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $userId = $data['user_id'] ?? $request->user()->id;

        // 检查权限（只有管理员或用户本人可以操作）
        if ($userId !== $request->user()->id && ! $request->user()->hasRole('admin')) {
            return response()->json([
                'code' => 403,
                'message' => '权限不足',
                'data' => null,
            ], 403);
        }

        $result = $this->pointsService->addPoints(
            $userId,
            $data['points'],
            null,
            'manual_add',
            ['description' => $data['description']]
        );

        if ($result['success']) {
            return response()->json([
                'code' => 200,
                'message' => $result['message'],
                'data' => [
                    'points' => $result['points'],
                    'message' => $result['message'],
                ],
            ], 200);
        }

        return response()->json([
            'code' => 400,
            'message' => $result['error'],
            'data' => null,
        ], 400);
    }

    /**
     * 使用积分.
     *
     * @authenticated
     */
    public function usePoints(PointsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->pointsService->usePoints(
            $request->user()->id,
            $data['points'],
            $data['description'],
            $data['context'] ?? []
        );

        if ($result['success']) {
            return response()->json([
                'code' => 200,
                'message' => $result['message'],
                'data' => [
                    'points' => $result['points'],
                    'message' => $result['message'],
                ],
            ], 200);
        }

        return response()->json([
            'code' => 400,
            'message' => $result['error'],
            'data' => null,
        ], 400);
    }

    /**
     * 检查积分是否足够
     *
     * @authenticated
     */
    public function checkPoints(PointsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $hasEnough = $this->pointsService->hasEnoughPoints(
            $request->user()->id,
            $data['required_points']
        );

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'hasEnough' => $hasEnough,
                'requiredPoints' => $data['required_points'],
                'availablePoints' => $this->pointsService->getUserPointsStats($request->user()->id)['current_points'],
            ],
        ], 200);
    }

    /**
     * 获取可用的积分规则.
     *
     * @authenticated
     */
    public function getAvailableRules(Request $request): JsonResponse
    {
        $rules = PointsRule::where('status', 'active')
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->orderBy('priority', 'desc')
            ->get();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => PointsResource::rule($rules),
                'total' => $rules->count(),
            ],
        ], 200);
    }

    /**
     * 触发积分规则（用于前端手动触发）.
     *
     * @authenticated
     */
    public function triggerRule(TriggerPointsRuleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->pointsService->triggerPointsRules(
            $request->user()->id,
            $data['action'],
            $data['context'] ?? []
        );

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'results' => $result,
                'total' => count($result),
                'successCount' => count(array_filter($result, fn ($r) => $r['success'] ?? false)),
            ],
        ], 200);
    }

    /**
     * 批量发放积分（管理员功能）.
     *
     * @authenticated
     */
    public function batchAddPoints(PointsRequest $request): JsonResponse
    {
        // 检查管理员权限
        if (! $request->user()->hasRole('admin')) {
            return response()->json([
                'code' => 403,
                'message' => '权限不足',
                'data' => null,
            ], 403);
        }

        $data = $request->validated();

        $result = $this->pointsService->batchAddPoints(
            $data['user_ids'],
            $data['points'],
            $data['description']
        );

        return response()->json([
            'code' => 200,
            'message' => '批量发放积分完成',
            'data' => [
                'total' => $result['total'],
                'successCount' => $result['success_count'],
                'failCount' => $result['fail_count'],
            ],
        ], 200);
    }

    /**
     * 获取积分规则详情.
     *
     * @authenticated
     */
    public function getRuleDetails(int $ruleId): JsonResponse
    {
        $rule = PointsRule::findOrFail($ruleId);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'rule' => PointsResource::rule($rule),
                'isActive' => $rule->isActive(),
            ],
        ], 200);
    }

    /**
     * 获取用户积分记录统计
     *
     * @authenticated
     */
    public function getRecordStats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // 使用 amount 字段（实际数据库字段名）
        $stats = [
            'total_earned' => PointsRecord::where('user_id', $userId)
                ->where('amount', '>', 0)
                ->sum('amount'),
            'total_used' => abs(PointsRecord::where('user_id', $userId)
                ->where('amount', '<', 0)
                ->sum('amount')),
            'today_earned' => PointsRecord::where('user_id', $userId)
                ->where('amount', '>', 0)
                ->whereDate('created_at', today())
                ->sum('amount'),
            'this_week_earned' => PointsRecord::where('user_id', $userId)
                ->where('amount', '>', 0)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->sum('amount'),
            'this_month_earned' => PointsRecord::where('user_id', $userId)
                ->where('amount', '>', 0)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
        ];

        // 转换为驼峰格式
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'totalEarned' => $stats['total_earned'],
                'totalUsed' => $stats['total_used'],
                'todayEarned' => $stats['today_earned'],
                'thisWeekEarned' => $stats['this_week_earned'],
                'thisMonthEarned' => $stats['this_month_earned'],
            ],
        ], 200);
    }
}
