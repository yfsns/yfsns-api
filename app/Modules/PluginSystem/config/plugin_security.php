<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 插件安全配置
    |--------------------------------------------------------------------------
    |
    | 这些配置控制插件系统的安全行为，确保插件错误不会影响主系统
    |
    */

    /*
    |--------------------------------------------------------------------------
    | 语法验证设置
    |--------------------------------------------------------------------------
    */
    'syntax_validation' => [
        'enabled' => env('PLUGIN_SYNTAX_VALIDATION_ENABLED', true),

        // 语法检查严格程度
        'strict_mode' => env('PLUGIN_SYNTAX_STRICT_MODE', true),

        // 允许的PHP版本兼容性
        'php_version_compatibility' => env('PLUGIN_PHP_COMPATIBILITY', PHP_VERSION),

        // 是否检查代码质量
        'check_code_quality' => env('PLUGIN_CHECK_CODE_QUALITY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 沙箱环境设置
    |--------------------------------------------------------------------------
    */
    'sandbox' => [
        'enabled' => env('PLUGIN_SANDBOX_ENABLED', true),

        // 最大执行时间（秒）
        'max_execution_time' => env('PLUGIN_MAX_EXECUTION_TIME', 30),

        // 最大内存使用（字节）
        'max_memory_usage' => env('PLUGIN_MAX_MEMORY_USAGE', 32 * 1024 * 1024), // 32MB

        // 禁用的函数列表
        'blocked_functions' => [
            'exec', 'shell_exec', 'system', 'passthru',
            'eval', 'create_function',
            'unlink', 'rmdir', 'mkdir',
            'file_put_contents', 'fwrite',
            'chmod', 'chown',
            'ini_set', 'set_time_limit',
        ],

        // 禁用的类
        'blocked_classes' => [
            // 可以在这里添加禁用的类
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 加载器设置
    |--------------------------------------------------------------------------
    */
    'loader' => [
        // 是否启用安全加载器
        'safe_loading' => env('PLUGIN_SAFE_LOADING', true),

        // 插件加载失败重试次数
        'max_retry_attempts' => env('PLUGIN_MAX_RETRY_ATTEMPTS', 3),

        // 加载超时时间（秒）
        'load_timeout' => env('PLUGIN_LOAD_TIMEOUT', 10),

        // 是否缓存加载结果
        'cache_loaded_plugins' => env('PLUGIN_CACHE_LOADED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 健康监控设置
    |--------------------------------------------------------------------------
    */
    'health_monitor' => [
        'enabled' => env('PLUGIN_HEALTH_MONITOR_ENABLED', true),

        // 健康检查缓存时间（秒）
        'cache_ttl' => env('PLUGIN_HEALTH_CACHE_TTL', 3600), // 1小时

        // 健康检查间隔（秒）
        'check_interval' => env('PLUGIN_HEALTH_CHECK_INTERVAL', 300), // 5分钟

        // 健康阈值
        'thresholds' => [
            'max_unhealthy_plugins_ratio' => env('PLUGIN_MAX_UNHEALTHY_RATIO', 0.5), // 50%
            'max_errors_per_plugin' => env('PLUGIN_MAX_ERRORS_PER_PLUGIN', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 错误处理设置
    |--------------------------------------------------------------------------
    */
    'error_handling' => [
        // 是否记录插件错误到单独的日志文件
        'separate_log_file' => env('PLUGIN_SEPARATE_LOG', true),

        // 日志文件名
        'log_filename' => env('PLUGIN_LOG_FILENAME', 'plugin_errors.log'),

        // 是否发送错误通知
        'send_notifications' => env('PLUGIN_SEND_NOTIFICATIONS', false),

        // 通知接收者
        'notification_recipients' => env('PLUGIN_NOTIFICATION_RECIPIENTS', ''),

        // 错误恢复策略
        'recovery_strategy' => env('PLUGIN_RECOVERY_STRATEGY', 'isolate'), // isolate, disable, ignore
    ],

    /*
    |--------------------------------------------------------------------------
    | 插件隔离设置
    |--------------------------------------------------------------------------
    */
    'isolation' => [
        // 是否启用插件隔离
        'enabled' => env('PLUGIN_ISOLATION_ENABLED', true),

        // 隔离级别：process, thread, namespace
        'level' => env('PLUGIN_ISOLATION_LEVEL', 'namespace'),

        // 共享资源
        'shared_resources' => [
            'database' => env('PLUGIN_SHARE_DATABASE', true),
            'cache' => env('PLUGIN_SHARE_CACHE', true),
            'filesystem' => env('PLUGIN_SHARE_FILESYSTEM', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 开发环境设置
    |--------------------------------------------------------------------------
    */
    'development' => [
        // 开发模式
        'debug_mode' => env('PLUGIN_DEBUG_MODE', env('APP_DEBUG', false)),

        // 显示详细错误信息
        'verbose_errors' => env('PLUGIN_VERBOSE_ERRORS', env('APP_DEBUG', false)),

        // 允许不安全的操作（仅开发环境）
        'allow_unsafe_operations' => env('PLUGIN_ALLOW_UNSAFE', false),
    ],
];
