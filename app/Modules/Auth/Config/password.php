<?php

/**
 * 密码配置 - 声明式配置系统
 */

/**
 * 声明式配置架构
 *
 * 定义密码安全相关的配置项
 */
$declarativeSchema = [
    'strength_level' => [
        'type' => 'string',
        'default' => 'weak',
        'description' => '密码强度级别：weak（弱）/medium（中等）/strong（强）',
        'options' => [
            ['value' => 'weak', 'label' => '弱密码', 'description' => '长度大于等于6个字符'],
            ['value' => 'medium', 'label' => '中等密码', 'description' => '包含字母和数字，长度大于等于6个字符'],
            ['value' => 'strong', 'label' => '强密码', 'description' => '包含大小写字母、数字和特殊符号，长度大于等于8个字符'],
        ],
    ],
    'min_length' => [
        'type' => 'integer',
        'default' => 6,
        'description' => '密码最小长度',
        'min' => 6,
        'max' => 128,
    ],
    'require_special_chars' => [
        'type' => 'boolean',
        'default' => false,
        'description' => '是否要求包含特殊字符',
    ],
    'require_numbers' => [
        'type' => 'boolean',
        'default' => false,
        'description' => '是否要求包含数字',
    ],
    'require_uppercase' => [
        'type' => 'boolean',
        'default' => false,
        'description' => '是否要求包含大写字母',
    ],
    'require_lowercase' => [
        'type' => 'boolean',
        'default' => false,
        'description' => '是否要求包含小写字母',
    ],
    'prevent_common_passwords' => [
        'type' => 'boolean',
        'default' => true,
        'description' => '是否防止使用常见密码',
    ],
    'password_history_count' => [
        'type' => 'integer',
        'default' => 5,
        'description' => '密码历史记录数量（防止重复使用最近的密码）',
        'min' => 0,
        'max' => 20,
    ],
    'password_expiry_days' => [
        'type' => 'integer',
        'default' => 90,
        'description' => '密码过期天数（0表示永不过期）',
        'min' => 0,
        'max' => 365,
    ],
    'max_login_attempts' => [
        'type' => 'integer',
        'default' => 5,
        'description' => '最大登录尝试次数',
        'min' => 1,
        'max' => 20,
    ],
    'lockout_duration_minutes' => [
        'type' => 'integer',
        'default' => 15,
        'description' => '账户锁定持续时间（分钟）',
        'min' => 1,
        'max' => 1440,
    ],
];

/**
 * 密码配置 - 直接使用配置文件
 *
 * 为什么不使用数据库配置？
 * 1. 密码策略通常是系统级别的配置，不需要动态修改
 * 2. 通过环境变量可以为不同环境设置不同的策略
 * 3. 避免了配置管理的复杂度
 * 4. 重启应用即可生效新配置
 */
return [
    // ========== 核心配置 ==========

    // 密码强度级别：weak（弱）/medium（中等）/strong（强）
    // 可以通过环境变量或直接修改此文件来改变
    'strength_level' => env('PASSWORD_STRENGTH_LEVEL', 'weak'),

    // 密码最小长度
    'min_length' => (int) env('PASSWORD_MIN_LENGTH', 6),

    // ========== 密码组成要求 ==========

    // 是否要求包含特殊字符
    'require_special_chars' => env('PASSWORD_REQUIRE_SPECIAL_CHARS', false),

    // 是否要求包含数字
    'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', false),

    // 是否要求包含大写字母
    'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', false),

    // 是否要求包含小写字母
    'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', false),

    // ========== 安全功能 ==========

    // 是否防止使用常见密码
    'prevent_common_passwords' => env('PASSWORD_PREVENT_COMMON', true),

    // 密码历史记录数量（防止重复使用最近的密码）
    'password_history_count' => (int) env('PASSWORD_HISTORY_COUNT', 5),

    // 密码过期天数（0表示永不过期）
    'password_expiry_days' => (int) env('PASSWORD_EXPIRY_DAYS', 90),

    // ========== 登录安全 ==========

    // 最大登录尝试次数
    'max_login_attempts' => (int) env('PASSWORD_MAX_LOGIN_ATTEMPTS', 5),

    // 账户锁定持续时间（分钟）
    'lockout_duration_minutes' => (int) env('PASSWORD_LOCKOUT_DURATION', 15),

    // ========== 配置元信息（用于管理界面） ==========

    'schema' => $declarativeSchema,
];
