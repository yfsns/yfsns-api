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
use Illuminate\Support\Facades\Route;

/*
||--------------------------------------------------------------------------
|| Post Routes
||--------------------------------------------------------------------------
||
|| 这里是动态模块前台路由定义
||
*/

// 统一的帖子列表接口 - 支持多种类型过滤和游标分页
// 使用全局限流（600次/分钟），count() 已优化（缓存30秒）
// 公开访问，但会根据登录状态返回不同的权限信息
// 添加api中间件组以支持Token验证和自动刷新
Route::middleware(['api'])->get('posts', [PostController::class, 'getPosts'])->name('posts.index');

// 动态详情 - 公开访问
// 使用全局限流（600次/分钟）
Route::get('posts/{post}', [PostController::class, 'getDetail'])->name('posts.show');

Route::middleware(['auth:sanctum'])->group(function (): void {
    // 动态基础操作（需要登录）
    Route::prefix('posts')->group(function (): void {
        Route::post('/', [PostController::class, 'store']);
        Route::put('{post}', [PostController::class, 'update']);
        Route::delete('{post}', [PostController::class, 'destroy']);
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

    // 文章相关路由
    Route::prefix('articles')->group(function (): void {
        Route::post('/', [ArticleController::class, 'store']);          // 创建文章
        Route::put('/{post}', [ArticleController::class, 'update']);    // 更新文章
        Route::delete('/{post}', [ArticleController::class, 'destroy']); // 删除文章
    });
});

// 公开访问的文章详情
Route::get('articles/{post}', [ArticleController::class, 'show'])->name('articles.show');
