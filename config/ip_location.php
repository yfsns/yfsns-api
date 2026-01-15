<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IP地址定位配置
    |--------------------------------------------------------------------------
    |
    | 这里配置IP地址定位服务的相关参数
    |
    */

    // IP定位API地址
    'api_url' => env('IP_LOCATION_API_URL', 'http://ip-api.com/json/'),

    // 缓存时间（秒）
    'cache_time' => env('IP_LOCATION_CACHE_TIME', 86400), // 24小时

    // 超时时间（秒）
    'timeout' => env('IP_LOCATION_TIMEOUT', 5),

    // 是否启用IP记录
    'enabled' => env('IP_LOCATION_ENABLED', true),

    // 本地IP地址范围
    'local_ranges' => [
        '127.0.0.0/8',      // 127.0.0.0 - 127.255.255.255
        '10.0.0.0/8',       // 10.0.0.0 - 10.255.255.255
        '172.16.0.0/12',    // 172.16.0.0 - 172.31.255.255
        '192.168.0.0/16',   // 192.168.0.0 - 192.168.255.255
        '::1/128',          // IPv6 localhost
        'fc00::/7',         // IPv6 unique local
        'fe80::/10',        // IPv6 link local
    ],

    // 代理IP头字段优先级
    'proxy_headers' => [
        'X-Forwarded-For',
        'X-Real-IP',
        'X-Client-IP',
    ],

    // 默认位置信息
    'default_location' => [
        'country' => '未知',
        'region' => '未知',
        'city' => '未知',
        'isp' => '未知',
        'location' => '未知-未知',
    ],

    // 本地网络位置信息
    'local_location' => [
        'country' => '中国',
        'region' => '本地',
        'city' => '本地',
        'isp' => '本地网络',
        'location' => '本地-本地',
    ],
];
