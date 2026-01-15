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
use App\Modules\Sms\Controllers\Admin\SmsController;
use Illuminate\Support\Facades\Route;

// 需要管理员认证的路由
Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
    // SMS 管理路由
    Route::prefix('sms')->group(function (): void {
    Route::post('test', [SmsController::class, 'test'])->name('admin.sms.test');

    // 通道管理
    Route::get('channels', [SmsController::class, 'getChannels'])->name('admin.sms.channels');
    Route::get('current', [SmsController::class, 'getCurrent'])->name('admin.sms.current');
    Route::post('{id}/enable', [SmsController::class, 'enable'])->name('admin.sms.enable');
    Route::post('{id}/disable', [SmsController::class, 'disable'])->name('admin.sms.disable');

    // 通道配置管理
    Route::post('channel/config', [SmsController::class, 'saveChannelConfig'])->name('admin.sms.channel.config.save');
    Route::post('channel/test', [SmsController::class, 'testChannelConfig'])->name('admin.sms.channel.test');
    Route::delete('channel/config', [SmsController::class, 'deleteChannelConfig'])->name('admin.sms.channel.config.delete');
    Route::get('channel/status', [SmsController::class, 'getChannelStatus'])->name('admin.sms.channel.status');
    });
});
