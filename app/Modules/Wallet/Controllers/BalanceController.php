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
use App\Modules\Wallet\Models\Transaction;
use App\Modules\Wallet\Requests\BalanceRequest;
use App\Modules\Wallet\Resources\BalanceResource;
use App\Modules\Wallet\Resources\TransactionResource;
use App\Modules\Wallet\Services\BalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 余额模块
 *
 * 余额控制器
 *
 * 主要功能：
 * 1. 提供余额相关的API接口
 * 2. 处理用户的余额操作请求
 * 3. 所有返回数据使用Resource格式化为驼峰格式
 */
class BalanceController extends Controller
{
    protected $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * 获取余额信息.
     *
     * @authenticated
     */
    public function getBalance(Request $request): JsonResponse
    {
        $balance = $this->balanceService->getBalance($request->user()->id);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => new BalanceResource($balance),
        ], 200);
    }

    /**
     * 充值
     *
     * @authenticated
     */
    public function recharge(BalanceRequest $request): JsonResponse
    {
        $data = $request->validated();

        $balance = $this->balanceService->recharge(
            $request->user()->id,
            $data['amount'],
            $data['description'] ?? '余额充值'
        );

        return response()->json([
            'code' => 200,
            'message' => '充值成功',
            'data' => new BalanceResource($balance),
        ], 200);
    }

    /**
     * 消费.
     *
     * @authenticated
     */
    public function consume(BalanceRequest $request): JsonResponse
    {
        $data = $request->validated();

        $balance = $this->balanceService->consume(
            $request->user()->id,
            $data['amount'],
            $data['description'] ?? '余额消费'
        );

        return response()->json([
            'code' => 200,
            'message' => '消费成功',
            'data' => new BalanceResource($balance),
        ], 200);
    }

    /**
     * 获取交易记录.
     *
     * @authenticated
     */
    public function getTransactions(BalanceRequest $request): JsonResponse
    {
        $data = $request->validated();

        $limit = $data['limit'] ?? 20;
        $type = $data['type'] ?? null;

        $query = Transaction::where('user_id', $request->user()->id);

        if ($type) {
            $query->where('type', $type);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => TransactionResource::collection($transactions),
                'total' => $transactions->count(),
                'limit' => $limit,
            ],
        ], 200);
    }

    /**
     * 获取余额统计信息.
     *
     * @authenticated
     */
    public function getStats(Request $request): JsonResponse
    {
        $stats = $this->balanceService->getBalanceStats($request->user()->id);

        // 转换为驼峰格式
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'currentBalance' => $stats['current_balance'] ?? 0,
                'totalRecharge' => $stats['total_recharge'] ?? 0,
                'totalConsume' => $stats['total_consume'] ?? 0,
                'frozenBalance' => $stats['frozen_balance'] ?? 0,
                'availableBalance' => $stats['available_balance'] ?? 0,
            ],
        ], 200);
    }
}
