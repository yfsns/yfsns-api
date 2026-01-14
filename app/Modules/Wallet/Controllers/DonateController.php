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
use App\Modules\Wallet\Requests\VirtualCoinRequest;
use App\Modules\Wallet\Resources\CoinTransactionResource;
use App\Modules\Wallet\Services\DonateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Donate Module
 *
 * Donate controller for handling user donations (打赏功能)
 * All returned data uses Resource format in camelCase
 */
class DonateController extends Controller
{
    protected $donateService;

    public function __construct(DonateService $donateService)
    {
        $this->donateService = $donateService;
    }

    /**
     * Donate to user
     */
    public function donateToUser(VirtualCoinRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Cannot donate to yourself
        if ($data['target_user_id'] == $request->user()->id) {
            return response()->json([
                'code' => 400,
                'message' => 'Cannot donate to yourself',
                'data' => null,
            ], 400);
        }

        $result = $this->donateService->donateToUser(
            $request->user()->id,
            $data['target_user_id'],
            $data['coins'],
            $data['description'] ?? 'Donate',
            $data['metadata'] ?? []
        );

        if ($result['success']) {
            return response()->json([
                'code' => 200,
                'message' => 'Donate successful',
                'data' => [
                    'success' => true,
                    'coins' => $result['coins'],
                    'targetUser' => [
                        'id' => (string) $data['target_user_id'],
                    ],
                    'message' => $result['message'],
                ],
            ], 200);
        }

        return response()->json([
            'code' => 400,
            'message' => $result['message'] ?? 'Donate failed',
            'data' => null,
        ], 400);
    }

    /**
     * Get user donate history
     */
    public function getHistory(VirtualCoinRequest $request): JsonResponse
    {
        $data = $request->validated();
        $limit = $data['limit'] ?? 20;

        $history = $this->donateService->getUserDonateHistory($request->user()->id, $limit);

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
     * Get sent donations
     */
    public function getSent(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $donates = $this->donateService->getSentDonates($request->user()->id, $limit);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => CoinTransactionResource::collection($donates),
                'total' => $donates->count(),
                'limit' => $limit,
            ],
        ], 200);
    }

    /**
     * Get received donations
     */
    public function getReceived(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $donates = $this->donateService->getReceivedDonates($request->user()->id, $limit);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => CoinTransactionResource::collection($donates),
                'total' => $donates->count(),
                'limit' => $limit,
            ],
        ], 200);
    }
}
