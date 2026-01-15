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
use App\Modules\Wallet\Controllers\BalanceController;
use App\Modules\Wallet\Controllers\CouponController;
use App\Modules\Wallet\Controllers\DonateController;
use App\Modules\Wallet\Controllers\PointsController;
use App\Modules\Wallet\Controllers\VirtualCoinController;
use App\Modules\Wallet\Controllers\WalletSecurityController;
use Illuminate\Support\Facades\Route;

/*
 * 钱包模块路由
 *
 * 所有路由都需要用户认证
 */
Route::middleware('auth:sanctum')->group(function (): void {
    // 余额相关路由
    Route::prefix('balance')->group(function (): void {
        Route::get('/', [BalanceController::class, 'getBalance']);
        Route::post('/recharge', [BalanceController::class, 'recharge']);
        Route::post('/consume', [BalanceController::class, 'consume']);
        Route::get('/transactions', [BalanceController::class, 'getTransactions']);
        Route::get('/stats', [BalanceController::class, 'getStats']);
    });

    // 钱包安全相关路由（简化版）
    Route::prefix('security')->group(function (): void {
        Route::get('/', [WalletSecurityController::class, 'getSecurity']);
        Route::put('/', [WalletSecurityController::class, 'updateSecurity']);
        Route::post('/payment-password', [WalletSecurityController::class, 'setPaymentPassword']);
        Route::post('/verify-password', [WalletSecurityController::class, 'verifyPaymentPassword']);
        Route::post('/check-limit', [WalletSecurityController::class, 'checkTransactionLimit']);
        Route::get('/logs', [WalletSecurityController::class, 'getSecurityLogs']);
        Route::post('/suspend', [WalletSecurityController::class, 'suspendWallet']);
        Route::post('/activate', [WalletSecurityController::class, 'activateWallet']);
    });

    // 积分相关路由
    Route::prefix('points')->group(function (): void {
        Route::get('/stats', [PointsController::class, 'getStats']);
        Route::get('/history', [PointsController::class, 'getHistory']);
        Route::get('/leaderboard', [PointsController::class, 'getLeaderboard']);
        Route::post('/add', [PointsController::class, 'addPoints']);
        Route::post('/use', [PointsController::class, 'usePoints']);
        Route::post('/check', [PointsController::class, 'checkPoints']);
        Route::get('/rules', [PointsController::class, 'getAvailableRules']);
        Route::post('/trigger', [PointsController::class, 'triggerRule']);
        Route::get('/rules/{ruleId}', [PointsController::class, 'getRuleDetails']);
        Route::get('/record-stats', [PointsController::class, 'getRecordStats']);

        // 管理员功能
        Route::post('/batch-add', [PointsController::class, 'batchAddPoints']);
    });

    // 虚拟币相关路由
    Route::prefix('coins')->group(function (): void {
        Route::get('/account', [VirtualCoinController::class, 'getAccount']);
        Route::post('/recharge', [VirtualCoinController::class, 'recharge']);
        Route::post('/consume', [VirtualCoinController::class, 'consumeCoins']);
        Route::get('/stats', [VirtualCoinController::class, 'getStats']);
        Route::get('/history', [VirtualCoinController::class, 'getHistory']);
        Route::get('/leaderboard', [VirtualCoinController::class, 'getLeaderboard']);
        Route::post('/check', [VirtualCoinController::class, 'checkCoins']);

        // 管理员功能
        Route::post('/reward', [VirtualCoinController::class, 'rewardCoins']);
        Route::post('/batch-reward', [VirtualCoinController::class, 'batchReward']);
    });

    // 优惠券相关路由
    Route::prefix('coupons')->group(function (): void {
        Route::post('/', [CouponController::class, 'create']);
        Route::post('/issue', [CouponController::class, 'issue']);
        Route::post('/use', [CouponController::class, 'use']);
        Route::get('/', [CouponController::class, 'list']);
        Route::get('/usable', [CouponController::class, 'usable']);
    });

    // 捐赠相关路由
    Route::prefix('donate')->group(function (): void {
        Route::post('/to-user', [DonateController::class, 'donateToUser']);
        Route::get('/history', [DonateController::class, 'getHistory']);
        Route::get('/sent', [DonateController::class, 'getSent']);
        Route::get('/received', [DonateController::class, 'getReceived']);
    });
});
