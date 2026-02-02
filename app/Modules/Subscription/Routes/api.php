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
use App\Modules\Subscription\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 订阅模块 API 路由
|--------------------------------------------------------------------------
*/

Route::prefix('subscriptions')
    ->middleware(['auth:sanctum'])
    ->group(function (): void {
        // 获取我的订阅列表
        Route::get('/', [SubscriptionController::class, 'index'])->name('subscriptions.index');

        // 创建订阅（通常通过支付流程，这里用于测试）
        Route::post('/', [SubscriptionController::class, 'store'])->name('subscriptions.store');

        // 取消订阅
        Route::delete('/', [SubscriptionController::class, 'destroy'])->name('subscriptions.destroy');
    });
