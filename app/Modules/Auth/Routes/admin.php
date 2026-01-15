<?php

use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 管理员认证模块路由
|--------------------------------------------------------------------------
|
| 路由加载说明：
| 1. 本文件在 app/Modules/Auth/Providers/AuthServiceProvider.php 中被加载
| 2. 加载时已添加 'api/admin' 前缀和 'api' 中间件
| 3. 路由设计遵循 RESTful 风格
|
| 控制器：AuthController - 处理管理员认证（复用通用认证逻辑）
|
*/

// 管理员认证路由 - 公开路由（无需认证，用于登录）
Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'adminLogin'])->name('admin.auth.login'); // 管理员登录（使用专用AdminAuthService优化性能）
});

// 管理员认证路由 - 受保护路由（需要管理员认证）
Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('auth')
    ->group(function (): void {
        Route::get('me', [AuthController::class, 'me'])->name('admin.auth.me');           // 获取当前管理员信息
        Route::post('logout', [AuthController::class, 'logout'])->name('admin.auth.logout'); // 管理员登出
});
