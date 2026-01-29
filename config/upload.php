<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 文件上传配置
    |--------------------------------------------------------------------------
    |
    | 这里配置文件上传的相关参数
    |
    */

    // 默认存储驱动
    'default' => env('UPLOAD_DRIVER', 'local'),

    // 文件大小限制（字节）
    'max_size' => env('UPLOAD_MAX_SIZE', 2 * 1024 * 1024), // 调整为2MB，与PHP配置一致

    // 允许的文件类型
    'allowed_types' => [
        'avatar' => ['jpg', 'jpeg', 'png', 'gif'],
        'static' => ['jpg', 'jpeg', 'png', 'gif', 'css', 'js', 'pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'attachment' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar'],
    ],

    // 存储路径配置
    'paths' => [
        'avatar' => 'uploads/avatars',
        'static' => 'uploads/static',
        'attachment' => 'uploads/attachments',
    ],

    // 文件上传限制配置
    'limits' => [
        // 允许的文件类型
        'allowed_mimes' => 'jpeg,jpg,png,gif,mp4,mov,avi,wmv,flv,webm,mkv',
        // 单个文件最大大小 (KB)，调整为2MB与PHP配置一致
        'max_file_size' => 2048, // 2MB
        // 批量上传最大文件数
        'max_batch_files' => 10,
    ],

    // 路径和文件名生成模式
    'patterns' => [
        'directory' => 'uploads/{module}/{date}',
        'filename' => '{user_id}_{timestamp}_{random}.{extension}',
    ],

    // 本地存储配置
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL') . '/storage',
    ],
];