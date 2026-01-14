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
use App\Modules\Wallet\Models\CoinTransaction;
use App\Modules\Wallet\Requests\VirtualCoinRequest;
use App\Modules\Wallet\Resources\CoinTransactionResource;
use App\Modules\Wallet\Resources\VirtualCoinResource;
use App\Modules\Wallet\Services\VirtualCoinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 金币模块
 *
 * 金币控制器（音符币）
 * 所有返回数据使用Resource格式化为驼峰格式
 */
class VirtualCoinController extends Controller
{
    protected $coinService;

    public function __construct(VirtualCoinService $coinService)
    {
        $this->coinService = $coinService;
    }

    /**
     * 获取金币账户信息.
     */
    public function getAccount(Request $request): JsonResponse
    {
        $account = $this->coinService->getCoinAccount($request->user()->id);

        $data = new VirtualCoinResource($account);
        $data->additional(['rmbValue' => $account->toRmb()]);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $data,
        ], 200);
    }

    /**
     * 充值金币
     */
    public function recharge(VirtualCoinRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->coinService->recharge(
            $request->user()->id,
            $data['rmb_amount'],
            $data['description'] ?? '充值金币'
        );

        if ($result['success']) {
            return response()->json([
                'code' => 200,
                'message' => '充值成功',
                'data' => [
                    'success' => true,
                    'coins' => $result['coins'],
                    'rmbAmount' => $result['rmb_amount'],
                    'message' => $result['message'],
                ],
            ], 200);
        }

        return response()->json([
            'code' => 400,
            'message' => $result['message'] ?? '充值失败',
            'data' => null,
        ], 400);
    }


    /**
     * 消费金币
     */
    public function consumeCoins(VirtualCoinRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->coinService->consumeCoins(
            $request->user()->id,
            $data['coins'],
            $data['description'],
            $data['metadata'] ?? []
        );

        if ($result['success']) {
            return response()->json([
                'code' => 200,
                'message' => '消费成功',
                'data' => [
                    'success' => true,
                    'coins' => $result['coins'],
                    'message' => $result['message'],
                ],
            ], 200);
        }

        return response()->json([
            'code' => 400,
            'message' => $result['message'] ?? '消费失败',
            'data' => null,
        ], 400);
    }

    /**
     * 获取金币统计信息.
     */
    public function getStats(Request $request): JsonResponse
    {
        $stats = $this->coinService->getUserCoinStats($request->user()->id);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => VirtualCoinResource::stats($stats),
        ], 200);
    }

    /**
     * 获取金币历史记录.
     */
    public function getHistory(VirtualCoinRequest $request): JsonResponse
    {
        $data = $request->validated();

        $limit = $data['limit'] ?? 20;
        $type = $data['type'] ?? null;

        $query = CoinTransaction::where('user_id', $request->user()->id)
            ->with('targetUser:id,username,nickname,avatar');

        if ($type) {
            $query->where('type', $type);
        }

        $history = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => CoinTransactionResource::collection($history),
                'total' => $history->count(),
                'limit' => $limit,
            ],
        ], 200);
    }

    /**
     * 获取金币排行榜.
     */
    public function getLeaderboard(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $leaderboard = $this->coinService->getCoinLeaderboard($limit);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => VirtualCoinResource::leaderboard($leaderboard),
                'total' => $leaderboard->count(),
                'limit' => $limit,
            ],
        ], 200);
    }

    /**
     * 检查金币是否足够
     */
    public function checkCoins(Request $request): JsonResponse
    {
        $requiredCoins = $request->input('required_coins'); // required in Request

        $hasEnough = $this->coinService->hasEnoughCoins(
            $request->user()->id,
            $requiredCoins
        );

        $account = $this->coinService->getCoinAccount($request->user()->id);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'hasEnough' => $hasEnough,
                'requiredCoins' => $requiredCoins,
                'availableCoins' => $account->balance - $account->frozen_balance,
                'rmbValue' => $account->toRmb(),
            ],
        ], 200);
    }


}
