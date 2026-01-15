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
use App\Modules\System\Controllers\Admin\ConfigController;
use App\Modules\System\Controllers\Admin\SysinfoController;
use App\Modules\System\Controllers\Admin\WebsiteConfigController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| System Admin Routes
|--------------------------------------------------------------------------
|
| 这里是系统模块后台路由定义
|
*/

// 需要管理员认证的路由
Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
    // 网站配置路由
    Route::get('website-config', [WebsiteConfigController::class, 'show']);
    Route::put('website-config', [WebsiteConfigController::class, 'update']);

// 系统配置项管理路由
Route::prefix('config')->group(function (): void {
    // 内容审核配置 - 必须放在前面，优先匹配
    Route::get('content/review', [ConfigController::class, 'getContentReviewConfig']);
    Route::put('content/review', [ConfigController::class, 'updateContentReviewConfig']);

    // 认证配置
    Route::get('auth', [ConfigController::class, 'getAuthConfig']);
    Route::put('auth', [ConfigController::class, 'updateAuthConfig']);

    // 获取配置
    Route::get('{group}/{key}', [ConfigController::class, 'get']);
    Route::get('{group}', [ConfigController::class, 'getGroup']);

    // 设置配置
    Route::post('{group}/{key}', [ConfigController::class, 'set']);

    // 删除配置
    Route::delete('{group}/{key}', [ConfigController::class, 'delete']);

    // 统计信息
    Route::get('/', [ConfigController::class, 'groups']);
    Route::get('stats/info', [ConfigController::class, 'stats']);
});

// 系统信息路由
Route::get('sysinfo', [SysinfoController::class, 'systemInfo']);

// 缓存管理路由
Route::prefix('cache')->group(function (): void {
    // 一键清除全部缓存
    Route::post('clear-all', [ConfigController::class, 'clearAllCache']);
});

});
