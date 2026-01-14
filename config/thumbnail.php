<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 缩略图配置
    |--------------------------------------------------------------------------
    |
    | 配置缩略图生成规则，包括尺寸、质量、存储路径等
    |
    */

    // 是否启用缩略图生成
    'enabled' => env('THUMBNAIL_ENABLED', true),

    // 缩略图规格定义
    'sizes' => [
        'small' => [
            'width' => 200,
            'height' => 200,
            'quality' => 80,
            'suffix' => '_thumb_small',  // 缩略图文件名后缀
            'mode' => 'cover',  // 裁剪模式: cover(填充), contain(适应), resize(缩放)
        ],
        'medium' => [
            'width' => 600,
            'height' => 600,
            'quality' => 85,
            'suffix' => '_thumb_medium',
            'mode' => 'cover',
        ],
        'large' => [
            'width' => 1200,
            'height' => 1200,
            'quality' => 90,
            'suffix' => '_thumb_large',
            'mode' => 'resize',  // 大图使用resize保持原始比例
        ],
    ],

    // 默认生成的缩略图规格（可以是上面定义的某个或多个）
    'default_sizes' => ['small', 'medium'],

    // 本地存储缩略图配置
    'local' => [
        'enabled' => true,  // 是否为本地存储生成物理缩略图
        'path' => 'thumbnails',  // 缩略图存储子目录
        'keep_original' => true,  // 是否保留原图
    ],

    // 云存储缩略图配置（使用URL参数动态生成）
    'cloud' => [
        'enabled' => true,  // 是否启用云存储动态缩略图

        // 阿里云OSS图片处理参数
        'oss' => [
            'enabled' => true,
            'process_key' => 'x-oss-process',  // OSS图片处理参数名
            'templates' => [
                'small' => 'image/resize,m_fill,w_200,h_200/quality,q_80',
                'medium' => 'image/resize,m_fill,w_600,h_600/quality,q_85',
                'large' => 'image/resize,m_lfit,w_1200,h_1200/quality,q_90',
                // 更多预定义模板
                'avatar' => 'image/resize,m_fill,w_100,h_100/quality,q_80/circle,r_50',
                'banner' => 'image/resize,m_fill,w_1920,h_400/quality,q_90',
            ],
        ],

        // 腾讯云COS图片处理参数
        'cos' => [
            'enabled' => true,
            'process_key' => 'imageMogr2',  // COS图片处理参数名
            'templates' => [
                'small' => 'thumbnail/200x200!/quality/80',
                'medium' => 'thumbnail/600x600!/quality/85',
                'large' => 'thumbnail/1200x1200/quality/90',
                // 更多预定义模板
                'avatar' => 'thumbnail/100x100!/quality/80/iradius/50',
                'banner' => 'thumbnail/1920x400!/quality/90',
            ],
        ],

        // 七牛云图片处理参数
        'qiniu' => [
            'enabled' => true,
            'templates' => [
                'small' => 'imageView2/1/w/200/h/200/q/80',
                'medium' => 'imageView2/1/w/600/h/600/q/85',
                'large' => 'imageView2/2/w/1200/h/1200/q/90',
            ],
        ],
    ],

    // 允许生成缩略图的MIME类型
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/bmp',
    ],

    // 允许生成缩略图的文件扩展名
    'allowed_extensions' => [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'bmp',
    ],

    // 缩略图驱动（使用哪个图片处理库）
    'driver' => env('THUMBNAIL_DRIVER', 'gd'),  // gd, imagick

    // 是否异步生成缩略图（队列）
    'async' => env('THUMBNAIL_ASYNC', false),

    // 缩略图缓存配置
    'cache' => [
        'enabled' => true,
        'ttl' => 86400 * 30,  // 缓存30天
    ],
];
