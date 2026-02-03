<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 投票系统配置
    |--------------------------------------------------------------------------
    */

    // 插件是否保持数据在卸载时
    'keep_data_on_uninstall' => env('VOTE_KEEP_DATA_ON_UNINSTALL', false),

    // 默认设置
    'defaults' => [
        'allow_guest' => env('VOTE_ALLOW_GUEST', false),
        'show_results' => env('VOTE_SHOW_RESULTS', true),
        'require_login' => env('VOTE_REQUIRE_LOGIN', true),
        'max_votes' => env('VOTE_MAX_VOTES', 1),
    ],

    // 限制设置
    'limits' => [
        'max_options_per_vote' => env('VOTE_MAX_OPTIONS', 20),
        'max_votes_per_user' => env('VOTE_MAX_VOTES_PER_USER', 5),
        'max_title_length' => env('VOTE_MAX_TITLE_LENGTH', 255),
        'max_description_length' => env('VOTE_MAX_DESCRIPTION_LENGTH', 1000),
    ],

    // 缓存设置
    'cache' => [
        'vote_results_ttl' => env('VOTE_CACHE_RESULTS_TTL', 300), // 5分钟
        'user_vote_status_ttl' => env('VOTE_CACHE_USER_STATUS_TTL', 3600), // 1小时
    ],

    // 权限设置
    'permissions' => [
        'create_vote' => 'vote.create',
        'edit_vote' => 'vote.edit',
        'delete_vote' => 'vote.delete',
        'view_vote' => 'vote.view',
        'vote' => 'vote.vote',
        'manage_vote' => 'vote.manage',
    ],

    // 投票类型
    'types' => [
        'single' => '单选',
        'multiple' => '多选',
    ],

    // 投票状态
    'statuses' => [
        'draft' => '草稿',
        'active' => '进行中',
        'paused' => '暂停',
        'ended' => '已结束',
        'cancelled' => '已取消',
    ],
];
