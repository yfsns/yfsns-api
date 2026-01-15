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

namespace App\Modules\Order\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Requests\GetOrdersRequest;
use App\Modules\Order\Requests\PayOrderRequest;
use App\Modules\Order\Requests\RefundOrderRequest;
use App\Modules\Order\Requests\StoreOrderRequest;
use App\Modules\Order\Resources\OrderResource;
use App\Modules\Order\Resources\OrderStatsResource;
use App\Modules\Order\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 订单模块
 *
 * 订单控制器
 * 所有返回数据使用Resource格式化为驼峰格式
 */
class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * 获取当前用户订单列表.
     *
     * @authenticated
     */
    public function index(GetOrdersRequest $request): JsonResponse
    {
        $data = $request->validated();
        $perPage = $data['per_page'] ?? 10;

        $orders = $this->orderService->getUserOrders(
            $request->user()->id,
            $data
        )->paginate($perPage);

        // 返回Laravel原生分页格式
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => OrderResource::collection($orders->items()),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
                'prev_page_url' => $orders->previousPageUrl(),
                'next_page_url' => $orders->nextPageUrl(),
            ]
        ]);
    }

    /**
     * 获取订单详情.
     *
     * @authenticated
     */
    public function show(string $orderNo, Request $request): JsonResponse
    {
        $order = $this->orderService->getOrderDetail($orderNo, $request->user()->id);

        if (! $order) {
            return response()->json([
                'code' => 404,
                'message' => '订单不存在',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => new OrderResource($order),
        ], 200);
    }

    /**
     * 创建订单.
     *
     * @authenticated
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $order = $this->orderService->createOrder($data, $request->user()->id);

        return response()->json([
            'code' => 200,
            'message' => '订单创建成功',
            'data' => new OrderResource($order),
        ], 200);
    }

    /**
     * 取消订单.
     *
     * @authenticated
     */
    public function cancel(string $orderNo, Request $request): JsonResponse
    {
        $this->orderService->cancelOrder($orderNo, $request->user()->id);

        return response()->json([
            'code' => 200,
            'message' => '订单已取消',
            'data' => null,
        ], 200);
    }

    /**
     * 支付订单.
     *
     * @authenticated
     */
    public function pay(string $orderNo, PayOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->orderService->payOrder(
            $orderNo,
            $request->user()->id,
            $data['pay_type']
        );

        $responseData = [
            'order' => new OrderResource($result['order']),
            'message' => $result['message'],
            'success' => $result['success'],
        ];

        // 如果有额外的支付数据，添加到响应中
        if (isset($result['paymentData'])) {
            $responseData['paymentData'] = $result['paymentData'];
        }

        return response()->json([
            'code' => 200,
            'message' => $result['success'] ? '支付成功' : $result['message'],
            'data' => $responseData,
        ], 200);
    }

    /**
     * 申请退款.
     *
     * @authenticated
     */
    public function refund(string $orderNo, RefundOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->orderService->refundOrder(
            $orderNo,
            $request->user()->id,
            $data['reason']
        );

        return response()->json([
            'code' => 200,
            'message' => '退款申请已提交',
            'data' => null,
        ], 200);
    }

    /**
     * 获取订单统计
     *
     * @authenticated
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->orderService->getOrderStats($request->user()->id);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => new OrderStatsResource($stats),
        ], 200);
    }
}
