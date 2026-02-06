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
use App\Modules\Post\Controllers\PostController;
use App\Modules\Post\Controllers\ArticleController;
use App\Modules\Post\Controllers\StoryController;
use App\Modules\Post\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

/*
||--------------------------------------------------------------------------
|| Post Routes
||--------------------------------------------------------------------------
||
|| 这里是动态模块前台路由定义
||
*/

// ========================================
// 公开访问的查询接口（无需认证）
// ========================================

// 动态查询接口（公开访问）
Route::prefix('posts')->group(function (): void {
    Route::get('/', [PostController::class, 'getPosts']);           // 获取动态列表
    Route::get('/{post}', [PostController::class, 'getDetail']);    // 获取动态详情
});

Route::middleware(['auth:sanctum'])->group(function (): void {
    // 动态管理操作（需要认证）
    Route::prefix('posts')->group(function (): void {
        Route::post('/', [PostController::class, 'store']);          // 创建动态
        Route::put('{post}', [PostController::class, 'update']);     // 更新动态
        Route::delete('{post}', [PostController::class, 'destroy']); // 删除动态
    });

    // 转发相关操作（作为posts的子资源）
    Route::prefix('posts')->group(function (): void {
        // 转发动态
        Route::post('/{id}/repost', [PostController::class, 'repost']);

        // 取消转发
        Route::delete('/{id}/repost', [PostController::class, 'unrepost']);

        // 获取动态的转发列表
        Route::get('/{id}/reposts', [PostController::class, 'getReposts']);
    });

    // 文章相关路由（需要认证的管理操作）
    Route::prefix('articles')->group(function (): void {
        Route::post('/', [ArticleController::class, 'store']);          // 创建文章
        Route::put('/{post}', [ArticleController::class, 'update']);    // 更新文章
        Route::delete('/{post}', [ArticleController::class, 'destroy']); // 删除文章
    });

    // 故事（图文）相关路由（需要认证的管理操作）
    Route::prefix('stories')->group(function (): void {
        Route::post('/', [StoryController::class, 'store']);          // 创建故事
        Route::delete('/{post}', [StoryController::class, 'destroy']); // 删除故事
    });

    // 视频相关路由（需要认证的管理操作）
    Route::prefix('videos')->group(function (): void {
        Route::post('/', [VideoController::class, 'store']);          // 创建视频
        Route::delete('/{post}', [VideoController::class, 'destroy']); // 删除视频
    });
});

// ========================================
// 公开访问的查询接口（无需认证）
// ========================================

// 文章查询接口（公开访问）
Route::prefix('articles')->group(function (): void {
    Route::get('/', [ArticleController::class, 'index']);           // 获取文章列表
    Route::get('/{post}', [ArticleController::class, 'show']);       // 获取文章详情
});

// 故事（图文）查询接口（公开访问）
Route::prefix('stories')->group(function (): void {
    Route::get('/', [StoryController::class, 'index']);           // 获取图文列表
    Route::get('/{post}', [StoryController::class, 'show']);       // 获取图文详情
});

// 视频查询接口（公开访问）
Route::prefix('videos')->group(function (): void {
    Route::get('/', [VideoController::class, 'index']);           // 获取视频列表
    Route::get('/{post}', [VideoController::class, 'show']);       // 获取视频详情
});

