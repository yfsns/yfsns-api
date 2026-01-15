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
use App\Modules\Order\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('orders')->group(function (): void {
    // 订单列表和统计
    Route::get('/', [OrderController::class, 'index']);           // 获取订单列表
    Route::get('/stats', [OrderController::class, 'stats']);      // 获取订单统计

    // 订单操作
    Route::post('/', [OrderController::class, 'store']);          // 创建订单
    Route::get('/{order_no}', [OrderController::class, 'show']);  // 获取订单详情
    Route::post('/{order_no}/cancel', [OrderController::class, 'cancel']);  // 取消订单
    Route::post('/{order_no}/pay', [OrderController::class, 'pay']);        // 支付订单
    Route::post('/{order_no}/refund', [OrderController::class, 'refund']);  // 申请退款
});
