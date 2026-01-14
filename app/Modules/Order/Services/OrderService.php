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

namespace App\Modules\Order\Services;

use App\Modules\Order\Models\Order;
use App\Modules\Wallet\Services\BalanceService;
use App\Modules\Wallet\Services\VirtualCoinService;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * 订单服务类.
 *
 * 处理订单相关的业务逻辑
 */
class OrderService
{
    protected $balanceService;

    protected $coinService;

    public function __construct(
        BalanceService $balanceService,
        VirtualCoinService $coinService
    ) {
        $this->balanceService = $balanceService;
        $this->coinService = $coinService;
    }

    /**
     * 创建订单.
     */
    public function createOrder(array $data, int $userId): Order
    {
        // 生成订单号
        $orderNo = $this->generateOrderNo();

        // 计算订单金额
        $amount = $data['amount'];
        $quantity = $data['quantity'] ?? 1;

        return Order::create([
            'order_no' => $orderNo,
            'user_id' => $userId,
            'quantity' => $quantity,
            'amount' => $amount,
            'status' => Order::STATUS_PENDING,
            'payment_status' => Order::PAYMENT_STATUS_UNPAID,
            'subject' => $data['subject'],
            'remark' => $data['remark'] ?? null,
        ]);
    }

    /**
     * 获取用户订单列表.
     */
    public function getUserOrders(int $userId, array $filters = [])
    {
        $query = Order::where('user_id', $userId)
            ->with(['user']);

        // 状态筛选
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 支付状态筛选
        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        // 排序
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query;
    }

    /**
     * 获取订单详情.
     */
    public function getOrderDetail(string $orderNo, int $userId): ?Order
    {
        return Order::where('order_no', $orderNo)
            ->where('user_id', $userId)
            ->with(['user'])
            ->first();
    }

    /**
     * 取消订单.
     */
    public function cancelOrder(string $orderNo, int $userId): bool
    {
        $order = $this->getOrderDetail($orderNo, $userId);

        if (! $order) {
            throw new Exception('订单不存在');
        }

        if (! $order->canCancel()) {
            throw new Exception('订单状态不允许取消');
        }

        return $order->update(['status' => Order::STATUS_CANCELLED]);
    }

    /**
     * 支付订单.
     */
    public function payOrder(string $orderNo, int $userId, string $payType): array
    {
        return DB::transaction(function () use ($orderNo, $userId, $payType) {
            $order = $this->getOrderDetail($orderNo, $userId);

            if (! $order) {
                throw new Exception('订单不存在');
            }

            if ($order->isPaid()) {
                throw new Exception('订单已支付');
            }

            if ($order->status !== Order::STATUS_PENDING) {
                throw new Exception('订单状态不允许支付');
            }

            // 更新支付状态
            $order->update([
                'payment_status' => Order::PAYMENT_STATUS_PAYING,
                'pay_type' => $payType,
            ]);

            // 根据支付类型处理
            switch ($payType) {
                case Order::PAY_TYPE_BALANCE:
                    return $this->payWithBalance($order);

                case Order::PAY_TYPE_COIN:
                    return $this->payWithCoin($order);

                case Order::PAY_TYPE_ALIPAY:
                case Order::PAY_TYPE_WECHAT:
                    return $this->payWithThirdParty($order, $payType);

                default:
                    throw new Exception('不支持的支付方式');
            }
        });
    }

    /**
     * 申请退款.
     */
    public function refundOrder(string $orderNo, int $userId, string $reason = ''): bool
    {
        return DB::transaction(function () use ($orderNo, $userId, $reason) {
            $order = $this->getOrderDetail($orderNo, $userId);

            if (! $order) {
                throw new Exception('订单不存在');
            }

            if (! $order->canRefund()) {
                throw new Exception('订单状态不允许退款');
            }

            // 更新订单状态为退款中
            $order->update([
                'status' => Order::STATUS_REFUNDING,
                'remark' => $reason,
            ]);

            // TODO: 根据支付方式处理退款
            // 余额支付：直接退回余额
            // 金币支付：直接退回金币
            // 第三方支付：调用第三方退款API

            if ($order->pay_type === Order::PAY_TYPE_BALANCE) {
                $this->balanceService->recharge(
                    $order->user_id,
                    $order->amount,
                    "订单退款：{$order->subject}"
                );

                $order->update(['status' => Order::STATUS_REFUNDED]);
            }

            return true;
        });
    }

    /**
     * 获取订单统计
     */
    public function getOrderStats(int $userId): array
    {
        $all = Order::where('user_id', $userId)->count();
        $pending = Order::where('user_id', $userId)->where('status', Order::STATUS_PENDING)->count();
        $paid = Order::where('user_id', $userId)->where('status', Order::STATUS_PAID)->count();
        $completed = Order::where('user_id', $userId)->where('status', Order::STATUS_COMPLETED)->count();
        $cancelled = Order::where('user_id', $userId)->where('status', Order::STATUS_CANCELLED)->count();
        $refunded = Order::where('user_id', $userId)->where('status', Order::STATUS_REFUNDED)->count();

        $totalAmount = Order::where('user_id', $userId)
            ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_COMPLETED])
            ->sum('amount');

        return [
            'all' => $all,
            'pending' => $pending,
            'paid' => $paid,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'refunded' => $refunded,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * 余额支付.
     */
    protected function payWithBalance(Order $order): array
    {
            $this->balanceService->consume(
                $order->user_id,
                $order->amount,
                "订单支付：{$order->subject}"
            );

            $order->update([
                'status' => Order::STATUS_PAID,
                'payment_status' => Order::PAYMENT_STATUS_SUCCESS,
                'paid_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => '支付成功',
                'order' => $order,
            ];
    }

    /**
     * 金币支付.
     */
    protected function payWithCoin(Order $order): array
    {
            // 金币转换为RMB（1元=10金币）
            $requiredCoins = $order->amount * 10;

            $result = $this->coinService->consumeCoins(
                $order->user_id,
                $requiredCoins,
                "订单支付：{$order->subject}",
                ['order_no' => $order->order_no]
            );

            if (! $result['success']) {
                throw new Exception($result['message'] ?? '金币不足');
            }

            $order->update([
                'status' => Order::STATUS_PAID,
                'payment_status' => Order::PAYMENT_STATUS_SUCCESS,
                'paid_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => '支付成功',
                'order' => $order,
            ];
    }

    /**
     * 第三方支付（支付宝/微信）.
     */
    protected function payWithThirdParty(Order $order, string $payType): array
    {
        // TODO: 接入第三方支付SDK
        // 这里返回支付配置，前端跳转到支付页面

        return [
            'success' => false,
            'message' => '请前往支付',
            'payment_url' => '', // 支付跳转URL
            'payment_params' => [], // 支付参数
            'order' => $order,
        ];
    }

    /**
     * 生成订单号.
     */
    protected function generateOrderNo(): string
    {
        return 'ORD' . date('YmdHis') . mt_rand(1000, 9999);
    }
}
