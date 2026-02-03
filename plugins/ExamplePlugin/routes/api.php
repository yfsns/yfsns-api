<?php

use Illuminate\Support\Facades\Route;
use Plugins\ExamplePlugin\Http\Controllers\Api\ExampleController;

/*
|--------------------------------------------------------------------------
| Example Plugin API Routes
|--------------------------------------------------------------------------
|
| 这些路由会在插件启用时自动注册到 /api/plugins/example_plugin/* 路径下
| 所有路由都会自动应用 'api' 中间件
|
*/

// 示例资源路由
Route::get('/examples', [ExampleController::class, 'index'])->name('example.index');
Route::get('/examples/{example}', [ExampleController::class, 'show'])->name('example.show');

// 需要权限验证的路由
Route::middleware(['auth:api'])->group(function () {
    Route::post('/examples', [ExampleController::class, 'store'])
        ->middleware('can:example.create')
        ->name('example.store');

    Route::put('/examples/{example}', [ExampleController::class, 'update'])
        ->middleware('can:example.edit')
        ->name('example.update');

    Route::delete('/examples/{example}', [ExampleController::class, 'destroy'])
        ->middleware('can:example.delete')
        ->name('example.destroy');
});

// 管理路由（需要管理员权限）
Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {
    Route::get('/stats', [ExampleController::class, 'stats'])
        ->middleware('can:example.admin')
        ->name('example.admin.stats');

    Route::post('/config', [ExampleController::class, 'updateConfig'])
        ->middleware('can:example.admin')
        ->name('example.admin.config');
});
