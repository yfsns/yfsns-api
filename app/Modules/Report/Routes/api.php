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
use App\Modules\Report\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Report Routes
|--------------------------------------------------------------------------
|
| 这里是举报模块路由定义
|
*/

/*
 * @group 举报模块
 * @name 举报模块
 * @description 用户举报相关功能接口
 */
// 需要认证的路由
Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::prefix('report')->group(function (): void {
    /*
     * 获取我的举报列表
     * @name 获取我的举报列表
     * @description 获取当前用户的举报历史记录
     */
    Route::get('/', [ReportController::class, 'index'])->name('report.index');

    /*
     * 获取举报详情
     * @name 获取举报详情
     * @description 获取指定举报的详细信息
     */
    Route::get('/{report}', [ReportController::class, 'show'])->name('report.show');

    /*
     * 提交举报
     * @name 提交举报
     * @description 提交新的举报信息
     */
    Route::post('/', [ReportController::class, 'store'])->name('report.store');

    /*
     * 取消举报
     * @name 取消举报
     * @description 取消待处理的举报
     */
    Route::delete('/{report}', [ReportController::class, 'cancel'])->name('report.cancel');
    });
});
