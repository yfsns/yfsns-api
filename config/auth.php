<?php
/**
 * 多端应用认证配置
 *
 * 本配置文件为支持 Next.js PC端、小程序、App 的多端应用量身定制
 *
 * 认证策略：
 * - Next.js PC端：Session认证（自动管理cookies）
 * - 小程序/App：Token认证（Laravel Sanctum）
 * - 管理员后台：Session认证
 *
 * 使用方式：
 * 1. Next.js PC端：正常发送请求，cookies自动处理
 * 2. 小程序/App：在请求头中添加 X-Platform: miniprogram 或 X-Platform: app
 * 3. 登录时会根据平台自动返回相应认证信息
 */

use Laravel\Sanctum\Sanctum;

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | 默认认证守卫和密码重置配置
    |
    */

    'defaults' => [
        'guard' => 'web',           // 默认守卫（传统用途）
        'passwords' => 'users',     // 默认密码重置配置
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | 认证守卫配置：支持多端应用的不同认证方式
    |
    | - web: 传统Web应用（session认证）
    | - spa: Next.js PC端（session认证，自动管理cookies）
    | - api: 小程序/App（token认证，Laravel Sanctum）
    |
    */

    'guards' => [
        // 传统 Web 应用守卫：使用 session 驱动（配合 Sanctum 的 SPA 模式）
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // 小程序 / App API 守卫：使用 Sanctum token 驱动
        'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | 用户提供者：定义如何从数据库或其他存储中检索用户
    |
    | 所有认证守卫都使用用户提供者来获取用户信息
    |
    | 支持: "database"（数据库表）, "eloquent"（Eloquent模型）
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Modules\User\Models\User::class,
        ],

        // 如果需要，可以添加其他用户提供者（如管理员）
        // 'admins' => [
        //     'driver' => 'eloquent',
        //     'model' => App\Models\Admin::class,
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | You may specify multiple password reset configurations if you have more
    | than one user table or model in the application and you want to have
    | separate password reset settings based on the specific user types.
    |
    | The expire time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of seconds before a password confirmation
    | times out and the user is prompted to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => 10800,

    /*
    |--------------------------------------------------------------------------
    | 多端认证配置
    |--------------------------------------------------------------------------
    |
    | 针对不同前端的认证配置
    |
    */

    // 注意：Sanctum配置已移至 config/sanctum.php

    // 登录安全配置
    'login' => [
        'max_attempts' => env('LOGIN_MAX_ATTEMPTS', 5),   // 登录失败最大次数
        'lockout_minutes' => env('LOGIN_LOCKOUT_MINUTES', 15), // 锁定时间（分钟）
    ],

    // 用户状态配置
    'user' => [
        'status' => [
            'active' => 1,     // 正常状态
            'disabled' => 0,   // 禁用状态
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 前端平台配置
    |--------------------------------------------------------------------------
    |
    | 不同前端平台的认证方式配置
    |
    */
    'platforms' => [
        'nextjs' => [
            'guard' => 'web',           // Next.js PC端使用 session 认证
            'middleware' => ['auth:web'],
            'description' => 'Next.js 开发的PC端应用',
        ],

        'miniprogram' => [
            'guard' => 'api',           // 小程序使用 token 认证
            'middleware' => ['auth:api'],
            'description' => '微信小程序',
        ],

        'app' => [
            'guard' => 'api',           // App使用 token 认证
            'middleware' => ['auth:api'],
            'description' => '移动端App应用',
        ],
    ],
];