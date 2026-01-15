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
return [
    /*
    |--------------------------------------------------------------------------
    | 搜索模块配置
    |--------------------------------------------------------------------------
    |
    | 这里定义了搜索模块的所有配置选项
    |
    */

    // 默认搜索限制
    'default_limit' => env('SEARCH_DEFAULT_LIMIT', 20),

    // 最大搜索限制
    'max_limit' => env('SEARCH_MAX_LIMIT', 100),

    // 搜索建议限制
    'suggestions_limit' => env('SEARCH_SUGGESTIONS_LIMIT', 10),

    // 热门搜索词限制
    'hot_searches_limit' => env('SEARCH_HOT_SEARCHES_LIMIT', 20),

    // 可搜索的模型类型
    'searchable_models' => [
        'posts' => App\Modules\Post\Models\Post::class,
        'users' => App\Models\User::class,
        'comments' => App\Modules\Comment\Models\Comment::class,
        'topics' => App\Modules\Topic\Models\Topic::class,
        'groups' => App\Modules\Group\Models\Group::class,
    ],

    // 搜索权重配置
    'weights' => [
        'title' => 10,
        'content' => 5,
        'username' => 8,
        'nickname' => 8,
        'bio' => 3,
        'name' => 10,
        'description' => 5,
    ],

    // 搜索缓存配置
    'cache' => [
        'enabled' => env('SEARCH_CACHE_ENABLED', true),
        'ttl' => env('SEARCH_CACHE_TTL', 300), // 5分钟
    ],

    // 搜索日志配置
    'logging' => [
        'enabled' => env('SEARCH_LOGGING_ENABLED', true),
        'level' => env('SEARCH_LOGGING_LEVEL', 'info'),
    ],

    // 搜索高亮配置
    'highlight' => [
        'enabled' => env('SEARCH_HIGHLIGHT_ENABLED', true),
        'max_length' => env('SEARCH_HIGHLIGHT_MAX_LENGTH', 200),
        'tag' => env('SEARCH_HIGHLIGHT_TAG', 'em'),
    ],

    // 搜索过滤器配置
    'filters' => [
        'enabled' => env('SEARCH_FILTERS_ENABLED', true),
        'default' => [
            'status' => 'normal',
            'is_active' => true,
        ],
    ],
];
