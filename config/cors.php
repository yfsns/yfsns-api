<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],

    'allowed_origins' => [
        'http://localhost',          // nginx前端 (端口80)
        'http://localhost:3000',
        'http://localhost:3001',
        'http://localhost:3002',
        'http://192.168.5.10:3001',
        'http://localhost:4173',
        'http://localhost:5173',  // 本地测试IP
        'http://localhost:8000',  // API 文档页面
        'http://127.0.0.1:8000',  // API 文档页面（IP）
        'http://www1.yfsns.cn',      // 服务器地址
        'https://www.yfsns.cn',      // 生产环境前端地址
        'https://dev.yfsns.cn',      // 开发环境前端地址
        'https://dev2.yfsns.cn',     // 开发环境前端地址
        'https://api2.yfsns.cn',     // API 地址
    ],

    'allowed_origins_patterns' => [
        '/^https?:\/\/.*\.yfsns\.cn$/',  // 匹配所有 yfsns.cn 的子域（http 和 https）
    ],

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'Accept',
        'X-CSRF-TOKEN',
        'X-Platform',      // 平台标识header
        'X-Client-Type',   // 客户端类型header（备用）
    ],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,

];
