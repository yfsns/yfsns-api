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
use App\Modules\Location\Controllers\LocationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Location API Routes
|--------------------------------------------------------------------------
|
| 定位服务API路由
|
*/

Route::prefix('api/v1/location')->middleware('api')->group(function (): void {
    // 逆地理编码（坐标转地址）
    Route::get('/reverse', [LocationController::class, 'reverseGeocode']);

    // 地理编码（地址转坐标）
    Route::get('/geocode', [LocationController::class, 'geocode']);

    // IP定位
    Route::get('/ip', [LocationController::class, 'getLocationByIp']);

    // 计算距离
    Route::get('/distance', [LocationController::class, 'calculateDistance']);

    // 获取驱动列表
    Route::get('/drivers', [LocationController::class, 'getDrivers']);

    // 清除缓存（需要认证）
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/cache/clear', [LocationController::class, 'clearCache']);
    });
});
