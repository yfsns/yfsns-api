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
use App\Modules\Report\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
    // Report 管理路由
    Route::prefix('reports')->group(function (): void {
        // 获取举报列表
        Route::get('/', [ReportController::class, 'index'])->name('admin.reports.index');

        // 获取举报统计数据（必须放在 {id} 路由前面）
        Route::get('/statistics', [ReportController::class, 'statistics'])->name('admin.reports.statistics');

        // 导出举报数据
        Route::get('/export', [ReportController::class, 'export'])->name('admin.reports.export');

        // 批量处理举报
        Route::post('/batch-handle', [ReportController::class, 'batchHandle'])->name('admin.reports.batch-handle');

        // 获取举报详情（参数路由必须放在最后）
        Route::get('/{id}', [ReportController::class, 'show'])->name('admin.reports.show');

        // 处理举报
        Route::post('/{id}/handle', [ReportController::class, 'handle'])->name('admin.reports.handle');
    });
});
