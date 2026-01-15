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
use App\Modules\Search\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

/*
||--------------------------------------------------------------------------
|| 搜索模块 API 路由
||--------------------------------------------------------------------------
||
|| 这里定义了搜索模块的所有 API 路由
||
*/

// 全局搜索
Route::get('/search', [SearchController::class, 'globalSearch']);

// 分类搜索
Route::get('/search/posts', [SearchController::class, 'searchPosts']);
Route::get('/search/users', [SearchController::class, 'searchUsers']);
Route::get('/search/comments', [SearchController::class, 'searchComments']);
Route::get('/search/topics', [SearchController::class, 'searchTopics']);

// 搜索建议和热门搜索
Route::get('/search/suggestions', [SearchController::class, 'getSuggestions']);
Route::get('/search/hot', [SearchController::class, 'getHotSearches']);
