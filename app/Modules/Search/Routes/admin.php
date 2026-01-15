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
use App\Modules\Search\Controllers\Admin\SearchAdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 搜索模块后台管理 API 路由
|--------------------------------------------------------------------------
|
| 这里定义了搜索模块的后台管理 API 路由
|
*/

// 需要管理员认证的路由
Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
    // 搜索统计
Route::get('/search/stats', [SearchAdminController::class, 'getSearchStats']);

// 热门搜索词管理
Route::get('/search/hot-words', [SearchAdminController::class, 'getHotWords']);
Route::post('/search/hot-words', [SearchAdminController::class, 'addHotWord']);
Route::put('/search/hot-words/{id}', [SearchAdminController::class, 'updateHotWord']);
Route::delete('/search/hot-words/{id}', [SearchAdminController::class, 'deleteHotWord']);

    // 搜索日志
    Route::get('/search/logs', [SearchAdminController::class, 'getSearchLogs']);
    Route::delete('/search/logs', [SearchAdminController::class, 'clearSearchLogs']);
});
