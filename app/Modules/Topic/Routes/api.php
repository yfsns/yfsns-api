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
use App\Modules\Topic\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Topic Routes
|--------------------------------------------------------------------------
|
| 这里是话题模块前台路由定义
|
*/

// 公开访问的话题接口
Route::prefix('topics')->group(function (): void {
    // 获取话题列表
    Route::get('/', [TopicController::class, 'index'])->name('topics.index');

    // 获取热门话题
    Route::get('/hot', [TopicController::class, 'getHotTopics'])->name('topics.hot');

    // 搜索话题
    Route::get('/search', [TopicController::class, 'searchTopics'])->name('topics.search');

    // 获取话题详情（使用ID）
    Route::get('/{id}', [TopicController::class, 'getTopicDetail'])->name('topics.detail')->where('id', '[0-9]+');

    // 获取话题趋势（使用ID）
    Route::get('/{id}/trends', [TopicController::class, 'getTopicTrends'])->name('topics.trends')->where('id', '[0-9]+');

    // 获取话题下的动态列表（使用ID）
    Route::get('/{id}/posts', [TopicController::class, 'getTopicPosts'])->name('topics.posts')->where('id', '[0-9]+');

    // 获取推荐话题列表（公开访问）
    Route::get('/recommend', [TopicController::class, 'recommend'])->name('topics.recommend');
});

// 需要认证的话题接口
Route::prefix('topics')->middleware(['auth:sanctum'])->group(function (): void {

    // 创建话题（前台用户可创建）
    Route::post('/', [TopicController::class, 'store'])->name('topics.store');
});

// 需要认证的话题管理接口
Route::prefix('topics')->middleware(['auth:sanctum'])->group(function (): void {
    // 获取话题统计信息
    Route::get('/{topicId}/stats', [TopicController::class, 'getTopicStats'])->name('topics.stats');
});
