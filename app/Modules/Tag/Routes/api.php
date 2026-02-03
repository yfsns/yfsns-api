<?php

use App\Modules\Tag\Controllers\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tag Routes
|--------------------------------------------------------------------------
|
| 这里是标签模块路由定义
|
*/

// 标签相关路由
Route::prefix('tags')->group(function (): void {
    // 获取标签列表（公开访问）
    Route::get('/', [TagController::class, 'index']);

    // 获取热门标签（公开访问）- 必须在{tag}之前
    Route::get('/popular', [TagController::class, 'popular']);

    // 获取系统标签（公开访问）- 必须在{tag}之前
    Route::get('/system', [TagController::class, 'system']);

    // 获取标签详情（公开访问）- 参数路由放在最后
    Route::get('/{tag}', [TagController::class, 'show'])->where('tag', '[a-zA-Z0-9_-]+');
});

// 需要认证的标签管理路由
Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::prefix('tags')->group(function (): void {
        // 创建标签
        Route::post('/', [TagController::class, 'store']);

        // 更新标签
        Route::put('/{tag}', [TagController::class, 'update'])->where('tag', '[0-9]+');

        // 删除标签
        Route::delete('/{tag}', [TagController::class, 'destroy'])->where('tag', '[0-9]+');

        // 批量操作标签（给内容添加/移除标签）
        Route::post('/batch-operation', [TagController::class, 'batchOperation']);
    });
});