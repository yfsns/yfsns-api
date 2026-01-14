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
use App\Modules\Comment\Controllers\Admin\CommentController as AdminCommentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Comment Admin Routes
|--------------------------------------------------------------------------
|
| 这里是评论模块后台路由定义
|
*/

// 需要管理员认证的路由
Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
    Route::prefix('comment')->name('admin.comment.')->group(function (): void {
    // 评论管理
    Route::get('/', [AdminCommentController::class, 'index'])->name('index');
    Route::get('/statistics', [AdminCommentController::class, 'statistics'])->name('statistics');
    Route::post('/batch-destroy', [AdminCommentController::class, 'batchDestroy'])->name('batchDestroy');
    Route::post('/batch-audit', [AdminCommentController::class, 'batchAudit'])->name('batchAudit');

    // 具体路由必须在通用路由之前
    Route::put('/{id}/status', [AdminCommentController::class, 'updateStatus'])->name('updateStatus');
    Route::put('/{id}/audit', [AdminCommentController::class, 'audit'])->name('audit');
    Route::get('/{id}', [AdminCommentController::class, 'show'])->name('show');
    Route::delete('/{id}', [AdminCommentController::class, 'destroy'])->name('destroy');
    });
});
