<?php

use App\Modules\Category\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Category Routes
|--------------------------------------------------------------------------
|
| 这里是分类模块路由定义
|
*/

// 分类相关路由
Route::prefix('categories')->group(function (): void {
    // 获取分类列表（公开访问）
    Route::get('/', [CategoryController::class, 'index']);

    // 获取分类详情（公开访问）
    Route::get('/{category}', [CategoryController::class, 'show'])->where('category', '[a-zA-Z0-9_-]+');

    // 获取分类树结构（公开访问）
    Route::get('/tree', [CategoryController::class, 'tree']);

    // 获取根分类（公开访问）
    Route::get('/root', [CategoryController::class, 'root']);

    // 获取子分类（公开访问）
    Route::get('/{category}/children', [CategoryController::class, 'children'])->where('category', '[0-9]+');
});

// 需要认证的分类管理路由
Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::prefix('categories')->group(function (): void {
        // 创建分类
        Route::post('/', [CategoryController::class, 'store']);

        // 更新分类
        Route::put('/{category}', [CategoryController::class, 'update'])->where('category', '[0-9]+');

        // 删除分类
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->where('category', '[0-9]+');

        // 移动分类
        Route::patch('/{category}/move', [CategoryController::class, 'move'])->where('category', '[0-9]+');

        // 批量更新排序
        Route::patch('/batch-sort-order', [CategoryController::class, 'batchUpdateSortOrder']);

        // 批量操作分类（给内容设置/添加/移除分类）
        Route::post('/batch-operation', [CategoryController::class, 'batchOperation']);
    });
});