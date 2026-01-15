<?php

use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 用户认证模块路由（重构版）
|--------------------------------------------------------------------------
|
| 路由加载说明：
| 1. 本文件在 app/Modules/Auth/Providers/AuthServiceProvider.php 中被加载
| 2. 加载时已添加 'api/v1' 前缀，所以所有路由都会自动添加 /api/v1 前缀
|
| 控制器职责划分：
| 1. RegisterController - 用户注册及注册验证码
| 2. LoginController    - 用户登录（密码登录、验证码登录）
| 3. TokenController    - Token管理（刷新、获取用户、登出）
|
| 完整路由示例：
|    - 注册：POST /api/v1/auth/register
|    - 登录：POST /api/v1/auth/login
|    - 短信登录：POST /api/v1/auth/login/sms
||    - 获取用户信息：GET /api/v1/auth/me
|    - 退出登录：POST /api/v1/auth/logout
|
*/

// ========================================
// 统一认证接口（支持多端认证）
// ========================================

Route::prefix('auth')->group(function (): void {

    // ============ 公开接口（无需认证） ============

    // 发送邮箱验证码
    Route::post('send-email-verification', [AuthController::class, 'sendEmailVerificationCode']);

    // 发送短信验证码
    Route::post('send-sms-verification', [AuthController::class, 'sendSmsVerificationCode']);

    // 用户注册 - 专用路由
    Route::prefix('register')->group(function (): void {
        Route::post('email', [AuthController::class, 'registerEmail']);
        Route::post('phone', [AuthController::class, 'registerPhone']);
        Route::post('username', [AuthController::class, 'registerUsername']);
    });

    // 用户登录 - 专用路由
    Route::prefix('login')->group(function (): void {
        // 统一密码登录（支持用户名/邮箱/手机号+密码）
        Route::post('password', [AuthController::class, 'loginPassword']);
        // 验证码登录
        Route::post('email', [AuthController::class, 'loginEmail']);
        Route::post('phone', [AuthController::class, 'loginPhone']);
        // 兼容旧接口（保留）
        Route::post('username', [AuthController::class, 'loginUsername']);
    });

    // 刷新token（小程序/App专用）
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');

    // ============ 认证检查接口 ============

    // 检查登录状态（公开访问，轻量级）
    // 支持多端：自动检测认证方式
    // 暂时移除 throttle 中间件进行测试
    Route::get('check', [AuthController::class, 'check']); // ->middleware('throttle:60,1')

    // ============ 需要认证的接口 ============

    // Next.js PC端认证路由组（Session认证）
    Route::middleware('auth:sanctum')->prefix('web')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // 小程序/App认证路由组（Token认证）
    Route::middleware('auth:sanctum')->prefix('api')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // 兼容性路由（自动检测认证方式）
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        // IP地理位置相关接口
        Route::get('ip-location', [AuthController::class, 'getIpLocation']); // 获取IP地理位置
        Route::post('update-ip-location', [AuthController::class, 'updateUserIpLocation']); // 更新用户IP地理位置
    });
});

