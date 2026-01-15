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
use App\Modules\Notification\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// 通知管理 - 用户权限
Route::prefix('notifications')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/user', [NotificationController::class, 'userList']);
    Route::get('/unread-count', [NotificationController::class, 'unreadCount']); // 获取未读数量（必须在通配符之前）
    Route::get('/{notification}', [NotificationController::class, 'show']);
    Route::delete('/{notification}', [NotificationController::class, 'destroy']);
    Route::post('/{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
});
