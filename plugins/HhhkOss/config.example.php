<?php
/**
 * HhhkOss 阿里云OSS插件配置文件示例
 * 复制此文件为 config.php 并填写实际的OSS配置信息
 */

return [
    // 阿里云AccessKey ID
    'OSS_ACCESS_KEY_ID' => 'LTAI5t6A7B8C9D0E1F2G3H4',

    // 阿里云AccessKey Secret
    'OSS_ACCESS_KEY_SECRET' => 'your_access_key_secret_here',

    // OSS存储桶名称
    'OSS_BUCKET' => 'your-bucket-name',

    // OSS访问域名（如：oss-cn-hangzhou.aliyuncs.com）
    'OSS_ENDPOINT' => 'oss-cn-hangzhou.aliyuncs.com',

    // OSS地域（可选，如：cn-hangzhou）
    'OSS_REGION' => 'cn-hangzhou',

    // 是否使用自定义域名（true/false）
    'OSS_IS_CNAME' => false,

    // 是否使用HTTPS（true/false）
    'OSS_SSL' => true,

    // 请求超时时间（秒，默认60）
    'OSS_TIMEOUT' => 60,
];
