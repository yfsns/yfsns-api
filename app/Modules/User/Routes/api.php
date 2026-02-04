<?php

use App\Modules\User\Controllers\AvatarController;
use App\Modules\User\Controllers\FollowController;
use App\Modules\User\Controllers\SecurityController;
use App\Modules\User\Controllers\UserController;
use App\Modules\User\Controllers\UserMentionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
|
| 这里是用户模块路由定义
|
*/

// 开放访问的路由（不需要认证）
Route::prefix('users')->group(function (): void {
    // 获取用户详情（个人主页/个人中心）- 开放访问
    // 支持传入用户名或"me"，后端自动处理
    Route::get('/{username}', [UserController::class, 'show'])->where('username', '[a-zA-Z0-9_-]+|me');
});

// 确保所有 API 请求返回 JSON
Route::middleware(['auth:sanctum'])->group(function (): void {
    // 前台路由
    Route::prefix('users')->group(function (): void {
        // 获取推荐用户列表（需要登录）
        Route::get('/recommend', [UserController::class, 'recommend']);

        // 获取当前用户信息
        Route::get('/me', [UserController::class, 'me']);

        // 获取个人资料（包含隐私信息）
        Route::get('/profile', [UserController::class, 'profile']);

        // 获取用户的转发列表
        Route::get('/{userId}/reposts', [UserController::class, 'getUserReposts']);

        // 获取被@动态列表
        Route::get('/me/mentioned-posts', [UserController::class, 'getMentionedPosts']);

        // 获取@统计信息
        Route::get('/me/mention-stats', [UserController::class, 'getMentionStats']);

        // 获取我的转发列表
        Route::get('/me/reposts', [UserController::class, 'getUserReposts']);

        // 更新个人资料
        Route::put('/profile', [UserController::class, 'update']);

        // 头像相关路由（声明式API）
        Route::prefix('avatar')->group(function (): void {
            // 上传头像
            Route::post('/', [AvatarController::class, 'upload']);

            // 获取头像信息
            Route::get('/info', [AvatarController::class, 'info']);

            // 获取头像审核状态（兼容旧接口）
            Route::get('/status', [AvatarController::class, 'status']);



        });

        // 注销账户（放在最后，POST 语义更明确）
        Route::post('/me/cancel', [UserController::class, 'cancel']);
    });

    // 关注相关路由
    Route::prefix('follows')->group(function (): void {
        // 批量检查关注状态（必须放在参数路由之前）
        Route::post('/batch-status', [FollowController::class, 'batchCheckFollowStatus']);

        // 关注用户
        Route::post('/{user}', [FollowController::class, 'follow']);

        // 取消关注
        Route::delete('/{user}', [FollowController::class, 'unfollow']);

        // 检查关注状态
        Route::get('/{user}/status', [FollowController::class, 'checkFollowStatus']);

        // 获取关注列表（支持查看指定用户的关注列表）
        Route::get('/following', [FollowController::class, 'following']);

        // 获取粉丝列表（支持查看指定用户的粉丝列表）
        Route::get('/followers', [FollowController::class, 'followers']);
    });

    // @相关路由
    Route::prefix('mentions')->group(function (): void {
        // 获取@记录列表
        Route::get('/', [UserMentionController::class, 'index']);

        // 获取@统计信息
        Route::get('/stats', [UserMentionController::class, 'stats']);

        // 获取未读@数量
        Route::get('/unread-count', [UserMentionController::class, 'unreadCount']);

        // 获取内容的@用户列表
        Route::get('/content-mentions', [UserMentionController::class, 'getContentMentions']);

        // 标记单个@记录为已读
        Route::patch('/{mention}/read', [UserMentionController::class, 'markAsRead']);

        // 批量标记@记录为已读
        Route::patch('/read', [UserMentionController::class, 'markAsReadBulk']);

        // 标记所有@记录为已读
        Route::patch('/read-all', [UserMentionController::class, 'markAllAsRead']);

        // 删除单个@记录
        Route::delete('/{mention}', [UserMentionController::class, 'destroy']);

        // 批量删除@记录
        Route::delete('/batch', [UserMentionController::class, 'destroyBulk']);
    });

    // 安全相关路由
    Route::prefix('user')->group(function (): void {
        Route::prefix('security')->group(function (): void {
            Route::post('bind-phone', [SecurityController::class, 'bindPhone']);
            Route::post('unbind-phone', [SecurityController::class, 'unbindPhone']);
            Route::post('bind-email', [SecurityController::class, 'bindEmail']);  // 绑定或换绑邮箱
            Route::post('bind-wechat', [SecurityController::class, 'bindWechat']);
            Route::post('unbind-wechat', [SecurityController::class, 'unbindWechat']);
            Route::post('change-password', [SecurityController::class, 'changePassword']);
        });
    });

    Route::get('user/search', [UserController::class, 'search']);
});
