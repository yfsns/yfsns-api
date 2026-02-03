<?php

use Illuminate\Support\Facades\Route;
use Plugins\VoteSystem\Http\Controllers\Admin\VoteAdminController;

Route::prefix('votes')->group(function (): void {
    // 投票管理
    Route::get('/', [VoteAdminController::class, 'index']);
    Route::post('/', [VoteAdminController::class, 'store']);
    Route::get('/{id}', [VoteAdminController::class, 'show']);
    Route::put('/{id}', [VoteAdminController::class, 'update']);
    Route::delete('/{id}', [VoteAdminController::class, 'destroy']);

    // 投票状态控制
    Route::post('/{id}/activate', [VoteAdminController::class, 'activate']);
    Route::post('/{id}/deactivate', [VoteAdminController::class, 'deactivate']);

    // 投票统计
    Route::get('/{id}/stats', [VoteAdminController::class, 'stats']);
    Route::get('/stats/overview', [VoteAdminController::class, 'overviewStats']);
});

// 插件配置
Route::prefix('config')->group(function (): void {
    Route::get('/', [VoteAdminController::class, 'getConfig']);
    Route::put('/', [VoteAdminController::class, 'updateConfig']);
});
