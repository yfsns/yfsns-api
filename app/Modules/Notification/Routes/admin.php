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
use App\Modules\Notification\Http\Controllers\EmailConfigController;
use App\Modules\Notification\Http\Controllers\NotificationSettingController;
use App\Modules\Notification\Http\Controllers\NotificationTemplateController;
use Illuminate\Support\Facades\Route;

// 需要管理员认证的路由
Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
    // 通知模板管理
    Route::prefix('templates')->group(function (): void {
    Route::get('/', [NotificationTemplateController::class, 'index']);
    Route::get('/{template}', [NotificationTemplateController::class, 'show']);
    Route::put('/{template}', [NotificationTemplateController::class, 'update']);
    Route::delete('/{template}', [NotificationTemplateController::class, 'destroy']);
    });

    // 通知设置管理
    Route::prefix('settings')->group(function (): void {
    Route::get('/', [NotificationSettingController::class, 'index']);
    Route::post('/', [NotificationSettingController::class, 'store']);
    Route::get('/{setting}', [NotificationSettingController::class, 'show']);
    Route::put('/{setting}', [NotificationSettingController::class, 'update']);
    Route::delete('/{setting}', [NotificationSettingController::class, 'destroy']);
    });

    // 邮件配置管理
    Route::prefix('email/configs')->group(function (): void {
    Route::get('/', [EmailConfigController::class, 'show']);
    Route::post('/', [EmailConfigController::class, 'update']); // 使用 POST 以兼容 CDN
    Route::post('/test', [EmailConfigController::class, 'test']);
    });
});
