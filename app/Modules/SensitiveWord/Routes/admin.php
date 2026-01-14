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
use App\Modules\SensitiveWord\Controllers\Admin\SensitiveWordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SensitiveWord Admin Routes
|--------------------------------------------------------------------------
|
| 这里是敏感词模块后台路由定义
|
*/

// 需要管理员认证的路由
Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
    // 敏感词管理
    Route::prefix('sensitive-words')->group(function (): void {
    Route::get('/', [SensitiveWordController::class, 'index']);              // 获取敏感词列表
    Route::get('/stats', [SensitiveWordController::class, 'stats']);          // 获取统计数据
    Route::get('/options', [SensitiveWordController::class, 'getOptions']);   // 获取配置选项
    Route::get('/export', [SensitiveWordController::class, 'export']);        // 导出敏感词
    Route::get('/logs', [SensitiveWordController::class, 'logs']);            // 获取命中日志
    Route::get('/{id}', [SensitiveWordController::class, 'show']);            // 获取详情
    Route::post('/', [SensitiveWordController::class, 'store']);              // 创建敏感词
    Route::put('/{id}', [SensitiveWordController::class, 'update']);          // 更新敏感词
    Route::delete('/{id}', [SensitiveWordController::class, 'destroy']);      // 删除敏感词
    Route::post('/batch-import', [SensitiveWordController::class, 'batchImport']); // 批量导入
    Route::post('/batch-destroy', [SensitiveWordController::class, 'batchDestroy']); // 批量删除
    Route::post('/test-filter', [SensitiveWordController::class, 'testFilter']); // 测试过滤
    });
});
