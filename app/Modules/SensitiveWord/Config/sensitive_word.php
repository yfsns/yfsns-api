<?php

/**
 * 敏感词配置 - SensitiveWord 模块配置
 */

/**
 * 敏感词过滤配置
 *
 * 定义敏感词过滤的行为和策略
 */
return [
    // ========== 核心配置 ==========

    // 是否启用敏感词过滤
    'enabled' => env('SENSITIVE_WORD_ENABLED', true),

    // 默认处理动作：pass（通过）/replace（替换）/review（审核）/reject（拒绝）
    'default_action' => env('SENSITIVE_WORD_DEFAULT_ACTION', 'replace'),

    // 默认替换符号
    'default_replacement' => env('SENSITIVE_WORD_DEFAULT_REPLACEMENT', '***'),

    // ========== 内容类型配置 ==========

    // 不同内容类型的处理策略
    'content_types' => [
        'post' => [
            'action' => env('SENSITIVE_WORD_POST_ACTION', 'review'), // 动态默认审核
            'enabled' => env('SENSITIVE_WORD_POST_ENABLED', true),
        ],
        'comment' => [
            'action' => env('SENSITIVE_WORD_COMMENT_ACTION', 'replace'), // 评论默认替换
            'enabled' => env('SENSITIVE_WORD_COMMENT_ENABLED', true),
        ],
        'thread' => [
            'action' => env('SENSITIVE_WORD_THREAD_ACTION', 'review'), // 话题默认审核
            'enabled' => env('SENSITIVE_WORD_THREAD_ENABLED', true),
        ],
        'nickname' => [
            'action' => env('SENSITIVE_WORD_NICKNAME_ACTION', 'reject'), // 昵称默认拒绝
            'enabled' => env('SENSITIVE_WORD_NICKNAME_ENABLED', true),
        ],
        'article' => [
            'action' => env('SENSITIVE_WORD_ARTICLE_ACTION', 'review'), // 文章默认审核
            'enabled' => env('SENSITIVE_WORD_ARTICLE_ENABLED', true),
        ],
        'message' => [
            'action' => env('SENSITIVE_WORD_MESSAGE_ACTION', 'replace'), // 私信默认替换
            'enabled' => env('SENSITIVE_WORD_MESSAGE_ENABLED', true),
        ],
    ],

    // ========== 性能配置 ==========

    // 缓存时间（分钟）
    'cache_minutes' => (int) env('SENSITIVE_WORD_CACHE_MINUTES', 60),

    // 批量处理大小
    'batch_size' => (int) env('SENSITIVE_WORD_BATCH_SIZE', 1000),

    // ========== 高级配置 ==========

    // 是否启用模糊匹配
    'fuzzy_match' => env('SENSITIVE_WORD_FUZZY_MATCH', false),

    // 拼音匹配
    'pinyin_match' => env('SENSITIVE_WORD_PINYIN_MATCH', false),

    // 繁简体转换匹配
    'traditional_match' => env('SENSITIVE_WORD_TRADITIONAL_MATCH', false),

    // ========== 日志配置 ==========

    // 是否记录命中日志
    'log_hits' => env('SENSITIVE_WORD_LOG_HITS', true),

    // 日志保留天数
    'log_retention_days' => (int) env('SENSITIVE_WORD_LOG_RETENTION_DAYS', 90),

    // ========== 管理配置 ==========

    // 最大敏感词长度
    'max_word_length' => (int) env('SENSITIVE_WORD_MAX_LENGTH', 50),

    // 支持的敏感词级别
    'levels' => [
        ['value' => 'low', 'label' => '低风险', 'description' => '轻微敏感'],
        ['value' => 'medium', 'label' => '中风险', 'description' => '一般敏感'],
        ['value' => 'high', 'label' => '高风险', 'description' => '严重敏感'],
        ['value' => 'critical', 'label' => '极高风险', 'description' => '非常严重'],
    ],

    // ========== 配置元信息 ==========

    'schema' => [
        'enabled' => [
            'type' => 'boolean',
            'default' => true,
            'description' => '是否启用敏感词过滤',
        ],
        'default_action' => [
            'type' => 'string',
            'default' => 'replace',
            'description' => '默认处理动作',
            'options' => [
                ['value' => 'pass', 'label' => '通过'],
                ['value' => 'replace', 'label' => '替换'],
                ['value' => 'review', 'label' => '审核'],
                ['value' => 'reject', 'label' => '拒绝'],
            ],
        ],
        'cache_minutes' => [
            'type' => 'integer',
            'default' => 60,
            'description' => '缓存时间（分钟）',
            'min' => 1,
            'max' => 1440,
        ],
        'log_hits' => [
            'type' => 'boolean',
            'default' => true,
            'description' => '是否记录命中日志',
        ],
    ],
];
