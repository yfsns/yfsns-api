<?php

use App\Modules\User\Controllers\Admin\AvatarController;
use App\Modules\User\Controllers\Admin\UserController;
use App\Modules\User\Controllers\Admin\UserPermissionController;
use App\Modules\User\Controllers\Admin\UserRoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Admin Routes
|--------------------------------------------------------------------------
|
| 这里是用户模块后台路由定义
|
*/

// 需要管理员认证的路由
Route::middleware(['auth:sanctum','admin'])->group(function (): void {
    // 用户管理路由
    Route::prefix('users')->group(function (): void {
        // 获取用户列表
        Route::get('/', [UserController::class, 'index']);

        // 创建用户
        Route::post('/', [UserController::class, 'store']);

        // 获取用户详情
        Route::get('/{id}', [UserController::class, 'show']);

        // 更新用户信息
        Route::put('/{id}', [UserController::class, 'update']);

        // 删除用户
        Route::delete('/{id}', [UserController::class, 'destroy']);

        // 批量删除用户
        Route::delete('/', [UserController::class, 'batchDestroy']);

        // 更新用户状态
        Route::patch('/{id}/status', [UserController::class, 'updateStatus']);
        Route::post('/{id}/status', [UserController::class, 'updateStatus']); // 兼容CDN不支持PATCH的情况

        // 重置用户密码
        Route::post('/{id}/reset-password', [UserController::class, 'resetPassword']);
    });

    // 用户角色管理路由
    Route::prefix('roles')->group(function (): void {
        // 获取角色列表
        Route::get('/', [UserRoleController::class, 'index']);

        // 创建角色
        Route::post('/', [UserRoleController::class, 'store']);

        // 获取角色详情
        Route::get('/{role}', [UserRoleController::class, 'show']);

        // 更新角色
        Route::put('/{role}', [UserRoleController::class, 'update']);

        // 删除角色
        Route::delete('/{role}', [UserRoleController::class, 'destroy']);

        // 批量删除角色
        Route::delete('/', [UserRoleController::class, 'batchDestroy']);

        // 更新角色状态
        Route::patch('/{role}/status', [UserRoleController::class, 'updateStatus']);
        Route::post('/{role}/status', [UserRoleController::class, 'updateStatus']); // 兼容CDN不支持PATCH的情况
        Route::patch('/{role}/permissions', [UserRoleController::class, 'updatePermissions']);
        Route::post('/{role}/permissions', [UserRoleController::class, 'updatePermissions']); // 兼容CDN不支持PATCH的情况
    });

    // 权限管理路由
    Route::prefix('permissions')->group(function (): void {
        Route::get('/', [UserPermissionController::class, 'index']);
        Route::get('/tree', [UserPermissionController::class, 'tree']);
        Route::get('/{slug}', [UserPermissionController::class, 'show']);
        // 以下接口已废弃，权限定义在配置文件中
        Route::post('/', [UserPermissionController::class, 'store']);
        Route::put('/{slug}', [UserPermissionController::class, 'update']);
        Route::delete('/{slug}', [UserPermissionController::class, 'destroy']);
    });

    // 头像审核管理路由
    Route::prefix('avatars')->group(function (): void {
        // 获取待审核头像列表
        Route::get('/pending', [AvatarController::class, 'pendingReviews']);

        // 审核头像
        Route::post('/{asset}/review', [AvatarController::class, 'review']);

        // 获取审核统计
        Route::get('/statistics', [AvatarController::class, 'statistics']);
    });

    // 系统配置管理路由
    Route::prefix('configs')->group(function (): void {
        // 配置分组列表
        Route::get('/', [App\Http\Controllers\Admin\ConfigController::class, 'index']);

        // 查看分组配置
        Route::get('/{group}', [App\Http\Controllers\Admin\ConfigController::class, 'show']);

        // 更新分组配置
        Route::put('/{group}', [App\Http\Controllers\Admin\ConfigController::class, 'update']);

        // 重置分组配置
        Route::post('/{group}/reset', [App\Http\Controllers\Admin\ConfigController::class, 'reset']);

        // 导出配置
        Route::get('/{group}/export', [App\Http\Controllers\Admin\ConfigController::class, 'export']);

        // 导入配置
        Route::post('/{group}/import', [App\Http\Controllers\Admin\ConfigController::class, 'import']);
    });
});
