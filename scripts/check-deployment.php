<?php
/**
 * Laravel部署检查脚本
 * 用于快速诊断常见的部署问题
 */

// 检查PHP版本
echo "=== PHP环境检查 ===\n";
echo "PHP版本: " . PHP_VERSION . "\n";
echo "PHP可执行文件: " . PHP_BINARY . "\n\n";

// 检查关键扩展
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath'];
echo "=== PHP扩展检查 ===\n";
foreach ($requiredExtensions as $ext) {
    $status = extension_loaded($ext) ? '✓' : '✗';
    echo "$status $ext\n";
}
echo "\n";

// 检查文件权限
echo "=== 文件权限检查 ===\n";
$checkFiles = [
    'storage/' => 'storage目录',
    'storage/logs/' => '日志目录',
    'storage/framework/' => '框架缓存目录',
    'storage/framework/sessions/' => 'Session目录',
    'storage/framework/views/' => '视图缓存目录',
    'storage/framework/cache/' => '缓存目录',
    'bootstrap/cache/' => '引导缓存目录',
    '.env' => '环境配置文件',
];

foreach ($checkFiles as $path => $description) {
    if (file_exists($path)) {
        $writable = is_writable($path) ? '✓' : '✗';
        echo "$writable $description ($path)\n";
    } else {
        echo "✗ $description ($path) - 文件/目录不存在\n";
    }
}
echo "\n";

// 检查Laravel环境
echo "=== Laravel环境检查 ===\n";
try {
    // 检查.env文件
    if (!file_exists('.env')) {
        echo "✗ .env文件不存在\n";
    } else {
        echo "✓ .env文件存在\n";

        // 检查关键配置
        $envContent = file_get_contents('.env');
        $checks = [
            'APP_KEY' => '应用密钥',
            'DB_CONNECTION' => '数据库连接',
            'DB_HOST' => '数据库主机',
            'DB_DATABASE' => '数据库名',
        ];

        foreach ($checks as $key => $desc) {
            if (strpos($envContent, "$key=") !== false) {
                // 简单检查是否有值
                preg_match("/$key=(.+)/", $envContent, $matches);
                $hasValue = isset($matches[1]) && !empty(trim($matches[1]));
                $status = $hasValue ? '✓' : '✗';
                echo "$status $desc 已配置\n";
            } else {
                echo "✗ $desc 未配置\n";
            }
        }
    }

    // 检查vendor目录
    if (!file_exists('vendor/autoload.php')) {
        echo "✗ Composer依赖未安装 (vendor/autoload.php不存在)\n";
    } else {
        echo "✓ Composer依赖已安装\n";
    }

    // 检查artisan文件
    if (!file_exists('artisan')) {
        echo "✗ Artisan命令文件不存在\n";
    } else {
        echo "✓ Artisan命令文件存在\n";
    }

} catch (Exception $e) {
    echo "✗ Laravel环境检查失败: " . $e->getMessage() . "\n";
}

echo "\n=== 部署建议 ===\n";
echo "1. 确保所有目录权限正确 (755 for dirs, 644 for files)\n";
echo "2. 确保storage和bootstrap/cache目录可写\n";
echo "3. 检查Nginx/Apache配置中的URL重写规则\n";
echo "4. 运行: php artisan config:clear && php artisan cache:clear\n";
echo "5. 检查PHP-FPM是否正在运行\n";
?>