<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 插件系统配置
    |--------------------------------------------------------------------------
    |
    | 这里配置插件系统的各种参数
    |
    */

    // 已启用的插件列表
    'enabled' => env('PLUGINS_ENABLED', []),

    // 插件目录路径
    'path' => env('PLUGINS_PATH', base_path('plugins')),

    // 插件缓存时间（秒）
    'cache_time' => env('PLUGINS_CACHE_TIME', 3600),

    // 是否自动扫描插件（建议关闭，使用缓存机制）
    // 开启后会在每次应用启动时检查插件，性能较低
    // 关闭后插件会被缓存，需要手动运行 php artisan plugin:cache-clear --refresh 来更新
    'auto_scan' => env('PLUGINS_AUTO_SCAN', false),

    // 插件钩子配置
    'hooks' => [
        // 用户相关钩子
        'user.registered' => '用户注册后',
        'user.login' => '用户登录后',
        'user.logout' => '用户登出后',
        'user.updated' => '用户信息更新后',
        'user.deleted' => '用户删除后',

        // 内容相关钩子
        'post.created' => '动态创建后',
        'post.updated' => '动态更新后',
        'post.deleted' => '动态删除后',
        'comment.created' => '评论创建后',
        'comment.updated' => '评论更新后',
        'comment.deleted' => '评论删除后',

        // 系统相关钩子
        'system.boot' => '系统启动后',
        'system.shutdown' => '系统关闭前',
        'cache.cleared' => '缓存清理后',
    ],

    // 插件要求检查
    'requirements' => [
        'php' => '8.1.0',
        'laravel' => '10.0.0',
        'extensions' => [
            'json',
            'mbstring',
            'openssl',
        ],
    ],

    // 插件安全配置
    'security' => [
        // 允许的插件目录
        'allowed_directories' => [
            'plugins',
        ],

        // 禁止的插件名称
        'forbidden_names' => [
            'admin',
            'system',
            'core',
        ],

        // 插件文件权限
        'file_permissions' => [
            'directories' => 0755,
            'files' => 0644,
        ],
    ],

    // 插件更新配置
    'updates' => [
        // 是否启用自动更新检查
        'auto_check' => env('PLUGINS_AUTO_UPDATE_CHECK', false),

        // 更新检查间隔（小时）
        'check_interval' => env('PLUGINS_UPDATE_CHECK_INTERVAL', 24),

        // 更新源配置
        'sources' => [
            'default' => [
                'type' => 'http',
                'url' => env('PLUGINS_UPDATE_SOURCE_URL', 'https://plugins.example.com'),
                'timeout' => 30,
            ],
        ],
    ],

    // 插件日志配置
    'logging' => [
        // 是否记录插件操作日志
        'enabled' => env('PLUGINS_LOGGING_ENABLED', true),

        // 日志级别
        'level' => env('PLUGINS_LOG_LEVEL', 'info'),

        // 日志通道
        'channel' => env('PLUGINS_LOG_CHANNEL', 'daily'),
    ],

    // 插件性能配置
    'performance' => [
        // 是否启用插件缓存
        'cache_enabled' => env('PLUGINS_CACHE_ENABLED', true),

        // 插件加载超时时间（秒）
        'load_timeout' => env('PLUGINS_LOAD_TIMEOUT', 30),

        // 钩子执行超时时间（秒）
        'hook_timeout' => env('PLUGINS_HOOK_TIMEOUT', 10),
    ],
];
