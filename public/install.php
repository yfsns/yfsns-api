<?php
/**
 * YFSNS 应用 Web 安装脚本.
 *
 * 安装完成后请删除此文件以确保安全
 *
 * 使用方法：
 * 1. 访问 http://your-domain.com/install.php
 * 2. 按照向导完成安装
 * 3. 安装完成后删除此文件
 */

// 禁用执行时间限制
set_time_limit(0);
ini_set('max_execution_time', 0);

// 错误报告
error_reporting(\E_ALL);
ini_set('display_errors', 1);

// 为AJAX请求设置全局错误处理器
if (isset($_POST['execute_command']) && $_POST['execute_command'] === '1') {
    // 自定义错误处理器，确保AJAX请求返回JSON
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'error' => "PHP Error: {$errstr}",
            'debug' => [
                'file' => basename($errfile),
                'line' => $errline,
                'type' => 'PHP Error',
                'errno' => $errno
            ]
        ]);
        exit(1);
    });

    // 自定义异常处理器
    set_exception_handler(function($exception) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'error' => $exception->getMessage(),
            'debug' => [
                'file' => basename($exception->getFile()),
                'line' => $exception->getLine(),
                'type' => get_class($exception)
            ]
        ]);
        exit(1);
    });
}

// 定义根目录
\define('BASE_PATH', \dirname(__DIR__));


// 检查 PHP 版本
if (version_compare(\PHP_VERSION, '8.2.0') < 0) {
    exit('需要 PHP 8.2 或更高版本，当前版本：' . \PHP_VERSION);
}

// 检查必要的PHP扩展
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'json', 'fileinfo'];
$missingExtensions = [];
foreach ($requiredExtensions as $ext) {
    if (! \extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

// 错误页面显示函数
function displayExtensionErrorPage($missingExtensions) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PHP扩展检查失败 - YFSNS 安装向导</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; padding: 20px; }
            .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); padding: 40px; }
            .error { background: #fee; border: 1px solid #fcc; color: #c33; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .extensions { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .extension { padding: 5px 0; border-bottom: 1px solid #eee; }
            .extension:last-child { border-bottom: none; }
            .btn { background: #dc3545; color: white; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px 5px 0 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>❌ PHP扩展检查失败</h1>
            <div class="error">
                <strong>缺少必要的PHP扩展：</strong>
                <div class="extensions">
                    <?php foreach ($missingExtensions as $ext): ?>
                        <div class="extension">• <?php echo htmlspecialchars($ext); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <h3>解决方案：</h3>
            <ol>
                <li>联系您的服务器管理员或主机提供商</li>
                <li>要求安装上述PHP扩展</li>
                <li>或者在php.ini中启用这些扩展</li>
                <li>重启Web服务器</li>
            </ol>
            <p><strong>Linux/Ubuntu/Debian:</strong></p>
            <code>sudo apt-get install php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip</code>

            <p><strong>CentOS/RHEL:</strong></p>
            <code>sudo yum install php-mysql php-mbstring php-xml php-curl php-zip</code>

            <br><br>
            <a href="javascript:history.back()" class="btn">返回</a>
            <button onclick="location.reload()" class="btn">重新检查</button>
        </div>
    </body>
    </html>
    <?php
    exit;
}

function displayPermissionErrorPage($permissionIssues) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>文件权限检查失败 - YFSNS 安装向导</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; padding: 20px; }
            .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); padding: 40px; }
            .error { background: #fee; border: 1px solid #fcc; color: #c33; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .permissions { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .permission { padding: 5px 0; border-bottom: 1px solid #eee; }
            .permission:last-child { border-bottom: none; }
            .btn { background: #dc3545; color: white; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px 5px 0 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>❌ 文件权限检查失败</h1>
            <div class="error">
                <strong>以下目录权限不足：</strong>
                <div class="permissions">
                    <?php foreach ($permissionIssues as $issue): ?>
                        <div class="permission">• <?php echo htmlspecialchars($issue); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <h3>解决方案：</h3>
            <ol>
                <li>使用FTP/SFTP客户端或SSH连接到服务器</li>
                <li>设置目录权限为755或775</li>
                <li>确保Web服务器用户（如www-data, apache, nginx）有写权限</li>
            </ol>
            <p><strong>Linux/Unix命令：</strong></p>
            <code>
                chmod -R 755 storage/<br>
                chmod -R 755 bootstrap/cache/<br>
                chown -R www-data:www-data storage/<br>
                chown -R www-data:www-data bootstrap/cache/
            </code>

            <p><strong>注意：</strong> 请将www-data替换为您的Web服务器用户。</p>

            <br><br>
            <a href="javascript:history.back()" class="btn">返回</a>
            <button onclick="location.reload()" class="btn">重新检查</button>
        </div>
    </body>
    </html>
    <?php
    exit;
}

function displayComposerErrorPage() {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Composer依赖检查失败 - YFSNS 安装向导</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; padding: 20px; }
            .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); padding: 40px; }
            .error { background: #fee; border: 1px solid #fcc; color: #c33; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .btn { background: #dc3545; color: white; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px 5px 0 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>❌ Composer依赖检查失败</h1>
            <div class="error">
                <strong>找不到vendor/autoload.php文件</strong>
                <p>这通常表示Composer依赖包没有正确安装。</p>
            </div>
            <h3>解决方案：</h3>
            <ol>
                <li>确保Composer已安装：<code>composer --version</code></li>
                <li>进入项目根目录：<code>cd /path/to/your/project</code></li>
                <li>安装依赖包：<code>composer install --no-dev --optimize-autoloader</code></li>
                <li>如果网络问题，可以使用国内镜像：<code>composer config repo.packagist composer https://mirrors.aliyun.com/composer/</code></li>
            </ol>

            <p><strong>注意：</strong> 如果您没有SSH访问权限，请联系服务器管理员或主机提供商协助安装Composer依赖。</p>

            <br><br>
            <a href="javascript:history.back()" class="btn">返回</a>
            <button onclick="location.reload()" class="btn">重新检查</button>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 移除全局权限检查，改为在环境检测阶段统一检查

// 加载 Laravel 自动加载文件
$autoloadPath = BASE_PATH . '/vendor/autoload.php';
if (! file_exists($autoloadPath)) {
    displayComposerErrorPage();
    exit;
}
require $autoloadPath;

// 加载 Laravel 应用
$app = require_once BASE_PATH . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// 定义常量
const DEFAULT_DB_HOST = '127.0.0.1';
const DEFAULT_DB_PORT = '3306';
const DEFAULT_APP_NAME = 'YFSNS';
const DEFAULT_APP_ENV = 'production';
const DEFAULT_APP_DEBUG = 'false';
const INSTALL_LOCK_FILE = 'storage/install.lock';

// 定义安装步骤
$steps = [
    'check' => ['name' => '环境检测', 'icon' => ''],
    'config' => ['name' => '配置数据库', 'icon' => ''],
    'install' => ['name' => '执行安装', 'icon' => ''],
    'app_config' => ['name' => '应用配置', 'icon' => ''],
    'complete' => ['name' => '安装完成', 'icon' => ''],
];

// .env 文件处理函数
function setEnvValue($key, $value, $filePath = null)
{
    if ($filePath === null) {
        $filePath = BASE_PATH . '/.env';
    }

    // 如果.env文件不存在，先从.env.example创建
    if (!file_exists($filePath)) {
        $examplePath = BASE_PATH . '/.env.example';
        if (file_exists($examplePath)) {
            copy($examplePath, $filePath);
        } else {
            // 如果没有.example文件，创建基本的.env内容
            $basicEnv = "APP_NAME=Laravel\nAPP_ENV=local\nAPP_DEBUG=true\nAPP_URL=http://localhost\n\nLOG_CHANNEL=stack\nDB_CONNECTION=mysql\nQUEUE_CONNECTION=sync\nSESSION_DRIVER=file\nCACHE_DRIVER=file\n\nMAIL_MAILER=log\n";
            file_put_contents($filePath, $basicEnv);
        }
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        throw new Exception("无法读取 .env 文件: {$filePath}");
    }

    // 转义特殊字符用于正则
    $escapedKey = preg_quote($key, '/');

    // 如果键已存在，则替换；否则追加
    if (preg_match("/^{$escapedKey}=/m", $content)) {
        $content = preg_replace("/^{$escapedKey}=.*$/m", "{$key}={$value}", $content);
    } else {
        $content .= "\n{$key}={$value}";
    }

    if (file_put_contents($filePath, $content) === false) {
        throw new Exception("无法写入 .env 文件: {$filePath}");
    }

    return true;
}

// 辅助函数：生成alert
function generateAlert($type, $title, $content = '', $listItems = []) {
    $html = '<div class="alert alert-' . $type . '">';
    if ($title) {
        $html .= '<strong>' . $title . '</strong> ';
    }
    if ($content) {
        $html .= $content;
    }
    if (!empty($listItems)) {
        $html .= '<ul style="margin: 10px 0; padding-left: 20px;">';
        foreach ($listItems as $item) {
            $html .= '<li>' . $item . '</li>';
        }
        $html .= '</ul>';
    }
    $html .= '</div>';
    return $html;
}

// 辅助函数：生成表单组
function generateFormGroup($label, $input, $help = '') {
    return '<div class="form-group">
        <label>' . $label . '</label>
        ' . $input . '
        ' . ($help ? '<small style="color: #666;">' . $help . '</small>' : '') . '
    </div>';
}

$currentStep = $_GET['step'] ?? 'check';
$error = null;
$success = null;

// 处理表单提交和AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理AJAX命令执行请求
    if (isset($_POST['execute_command']) && $_POST['execute_command'] === '1') {
        // 设置JSON响应头
        header('Content-Type: application/json');

        $action = $_POST['action'] ?? '';
        $stepIndex = (int)($_POST['step_index'] ?? 0);

        if (empty($action)) {
            echo json_encode(['success' => false, 'error' => 'Action不能为空']);
            exit;
        }

        try {
            $output = '';

            // 调试信息
            $output .= "=== 调试信息 ===\n";
            $output .= "Action: {$action}\n";
            $output .= "Step Index: {$stepIndex}\n";
            $output .= "BASE_PATH: " . BASE_PATH . "\n";
            $output .= "Current Dir: " . getcwd() . "\n";
            $output .= "PHP Version: " . PHP_VERSION . "\n\n";

            // 确保Laravel应用已加载
            if (!isset($app)) {
                $output .= "初始化Laravel应用...\n";
                $autoloadPath = BASE_PATH . '/vendor/autoload.php';
                if (!file_exists($autoloadPath)) {
                    throw new Exception("找不到vendor/autoload.php，请先部署vendor目录");
                }
                require $autoloadPath;
                $app = require_once BASE_PATH . '/bootstrap/app.php';
                $output .= "✓ Laravel应用初始化完成\n\n";
            }

            // 获取数据库配置
            $envPath = BASE_PATH . '/.env';
            $dbConfig = [
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => '',
                'username' => '',
                'password' => '',
            ];

            $output .= "正在读取数据库配置...\n";
            if (file_exists($envPath)) {
                $output .= "找到 .env 文件: {$envPath}\n";
                $envContent = file_get_contents($envPath);
                if ($envContent !== false) {
                    // 解析.env文件中的数据库配置
                    $lines = array_filter(array_map('trim', explode("\n", $envContent)));
                    foreach ($lines as $line) {
                        if (empty($line) || str_starts_with($line, '#')) {
                            continue;
                        }
                        $pos = strpos($line, '=');
                        if ($pos !== false) {
                            $key = trim(substr($line, 0, $pos));
                            $value = trim(substr($line, $pos + 1));
                            $value = trim($value, '"\'');

                            switch ($key) {
                                case 'DB_HOST':
                                    $dbConfig['host'] = $value;
                                    $output .= "DB_HOST: {$value}\n";
                                    break;
                                case 'DB_PORT':
                                    $dbConfig['port'] = $value;
                                    $output .= "DB_PORT: {$value}\n";
                                    break;
                                case 'DB_DATABASE':
                                    $dbConfig['database'] = $value;
                                    $output .= "DB_DATABASE: {$value}\n";
                                    break;
                                case 'DB_USERNAME':
                                    $dbConfig['username'] = $value;
                                    $output .= "DB_USERNAME: {$value}\n";
                                    break;
                                case 'DB_PASSWORD':
                                    $dbConfig['password'] = $value;
                                    $output .= "DB_PASSWORD: " . (empty($value) ? '(空)' : '已设置') . "\n";
                                    break;
                            }
                        }
                    }
                } else {
                    $output .= "无法读取 .env 文件内容\n";
                }
            } else {
                $output .= "未找到 .env 文件，使用默认配置\n";
            }
            $output .= "\n";

            // 根据action执行相应的命令
            switch ($action) {
                case 'test_db':
                    // 测试数据库连接
                    try {
                    $pdo = new PDO(
                            "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset=utf8mb4",
                            $dbConfig['username'],
                            $dbConfig['password'],
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                        $output = "数据库连接测试成功\n";
                        $output .= "主机: {$dbConfig['host']}:{$dbConfig['port']}\n";
                        $output .= "用户: {$dbConfig['username']}\n";
                        $output .= "数据库: {$dbConfig['database']}\n";
                    } catch (PDOException $e) {
                        throw new Exception('数据库连接失败: ' . $e->getMessage());
                    }
                    break;

                case 'migrate':
                    // 执行数据库迁移 - 使用 Laravel Artisan 命令
                    try {
                        $output = "开始执行数据库迁移...\n\n";

                        // 检查是否可以执行命令
                        if (!function_exists('shell_exec') && !function_exists('exec')) {
                            throw new Exception('服务器不支持 shell_exec 或 exec 函数，无法执行 Artisan 命令');
                        }

                        // 获取项目根目录
                        $projectRoot = BASE_PATH;
                        $output .= "项目根目录: {$projectRoot}\n";

                        // 检查 artisan 文件是否存在
                        $artisanFile = $projectRoot . '/artisan';
                        if (!file_exists($artisanFile)) {
                            throw new Exception('找不到 artisan 文件: ' . $artisanFile);
                        }
                        $output .= "✓ 找到 artisan 文件\n";

                        // 检查 PHP 可执行文件
                        $phpExecutable = 'php';
                        $output .= "PHP 可执行文件: {$phpExecutable}\n\n";

                        // 构建命令 - 使用 migrate:fresh 彻底删除所有表并重新创建
                        $command = "cd {$projectRoot} && {$phpExecutable} artisan migrate:fresh --force 2>&1";
                        $output .= "执行命令: {$command}\n\n";
                        $output .= "注意：此操作将删除所有现有数据表并重新创建\n\n";

                        // 执行命令
                        $startTime = microtime(true);
                        $result = shell_exec($command);
                        $endTime = microtime(true);
                        $executionTime = round($endTime - $startTime, 2);

                        $output .= "命令执行完成，用时: {$executionTime} 秒\n\n";
                        $output .= "📋 执行结果:\n";
                        $output .= "----------------------------------------\n";
                        $output .= $result;
                        $output .= "----------------------------------------\n\n";

                        // 分析执行结果
                        if (strpos($result, 'Dropped all tables') !== false || strpos($result, 'Dropping all tables') !== false) {
                            $output .= "  ✓ 已删除所有现有数据表\n";
                        }
                        if (strpos($result, 'Nothing to migrate') !== false) {
                            $output .= "  没有需要迁移的数据库变更\n";
                        } elseif (strpos($result, 'Migrated:') !== false || strpos($result, 'Migrating:') !== false) {
                            // 提取迁移数量
                            $migratedCount = substr_count($result, 'Migrated:');
                            $output .= "  ✓ 成功创建了 {$migratedCount} 个数据表\n";
                        }

                        // 检查是否有错误
                        if (strpos($result, 'ERROR') !== false ||
                            strpos($result, 'Error') !== false ||
                            strpos($result, 'Failed') !== false ||
                            strpos($result, 'Exception') !== false) {
                            $output .= "  执行过程中出现错误，请检查上述输出信息\n\n";
                            $output .= "🔄 <button onclick='location.reload()' class='btn btn-warning'>重新执行</button>\n";
                        } else {
                            $output .= " 数据库迁移执行成功！\n\n";
                            $output .= " <button onclick='window.location.href=\"?step=key_generate\"' class='btn btn-success'>下一步：生成应用密钥</button>\n";
                        }

                    } catch (Exception $e) {
                        $output = '数据库迁移失败: ' . $e->getMessage();
                        $output .= "\n\n 可能的原因:\n";
                        $output .= "- 数据库连接配置错误\n";
                        $output .= "- 数据库权限不足\n";
                        $output .= "- 迁移文件存在语法错误\n";
                        $output .= "- PHP 环境不支持 shell_exec 函数\n\n";
                        $output .= "🔄 <button onclick='location.reload()' class='btn btn-warning'>重新执行</button>\n";
                        throw $e;
                    }
                    break;

                case 'key_generate':
                    // 生成应用密钥
                    try {
                        $output = "开始生成应用密钥...\n\n";

                        // 检查是否可以执行命令
                        if (!function_exists('shell_exec') && !function_exists('exec')) {
                            throw new Exception('服务器不支持 shell_exec 或 exec 函数，无法执行 Artisan 命令');
                        }

                        // 获取项目根目录
                        $projectRoot = BASE_PATH;
                        $output .= "项目根目录: {$projectRoot}\n";

                        // 检查 artisan 文件是否存在
                        $artisanFile = $projectRoot . '/artisan';
                        if (!file_exists($artisanFile)) {
                            throw new Exception('找不到 artisan 文件: ' . $artisanFile);
                        }
                        $output .= "✓ 找到 artisan 文件\n";

                        // 检查 PHP 可执行文件
                        $phpExecutable = 'php';
                        $output .= "PHP 可执行文件: {$phpExecutable}\n\n";

                        // 1. 生成应用密钥 (APP_KEY)
                        $output .= "生成应用密钥 (APP_KEY)...\n";
                        $command1 = "cd {$projectRoot} && {$phpExecutable} artisan key:generate --force 2>&1";
                        $result1 = shell_exec($command1);
                        $output .= "执行命令: {$command1}\n";
                        $output .= "结果: " . trim($result1) . "\n\n";

                        // 检查是否有错误
                        $allResults = $result1;
                        if (strpos($allResults, 'ERROR') !== false ||
                            strpos($allResults, 'Error') !== false ||
                            strpos($allResults, 'Failed') !== false ||
                            strpos($allResults, 'Exception') !== false) {
                            $output .= "  密钥生成过程中出现错误，请检查上述输出信息\n\n";
                            $output .= "🔄 <button onclick='location.reload()' class='btn btn-warning'>重新生成</button>\n";
                        } else {
                            $output .= " 应用密钥生成成功！\n\n";
                            $output .= " <button onclick='window.location.href=\"?step=migrate\"' class='btn btn-success'>下一步：执行数据库迁移</button>\n";
                        }

                    } catch (Exception $e) {
                        $output = '密钥生成失败: ' . $e->getMessage();
                        $output .= "\n\n 可能的原因:\n";
                        $output .= "- .env文件权限问题\n";
                        $output .= "- PHP环境不支持shell_exec函数\n";
                        $output .= "- Artisan命令执行失败\n\n";
                        $output .= "🔄 <button onclick='location.reload()' class='btn btn-warning'>重新生成</button>\n";
                        throw $e;
                    }
                    break;

                case 'seed':
                    // 填充初始数据 - 使用 Laravel Artisan 命令
                    try {
                        $output = "开始填充初始数据...\n\n";

                        // 检查是否可以执行命令
                        if (!function_exists('shell_exec') && !function_exists('exec')) {
                            throw new Exception('服务器不支持 shell_exec 或 exec 函数，无法执行 Artisan 命令');
                        }

                        // 获取项目根目录
                        $projectRoot = BASE_PATH;
                        $output .= "项目根目录: {$projectRoot}\n";

                        // 检查 artisan 文件是否存在
                        $artisanFile = $projectRoot . '/artisan';
                        if (!file_exists($artisanFile)) {
                            throw new Exception('找不到 artisan 文件: ' . $artisanFile);
                        }
                        $output .= "✓ 找到 artisan 文件\n";

                        // 检查 PHP 可执行文件
                        $phpExecutable = 'php';
                        $output .= "PHP 可执行文件: {$phpExecutable}\n\n";

                        // 构建命令
                        $command = "cd {$projectRoot} && {$phpExecutable} artisan db:seed --force 2>&1";
                        $output .= "执行命令: {$command}\n\n";

                        // 执行命令
                        $startTime = microtime(true);
                        $result = shell_exec($command);
                        $endTime = microtime(true);
                        $executionTime = round($endTime - $startTime, 2);

                        $output .= "命令执行完成，用时: {$executionTime} 秒\n\n";
                        $output .= "📋 执行结果:\n";
                        $output .= "----------------------------------------\n";
                        $output .= $result;
                        $output .= "----------------------------------------\n\n";

                        // 分析执行结果
                        if (strpos($result, 'Seeded:') !== false || strpos($result, 'Seeding:') !== false) {
                            // 提取seeder数量
                            $seededCount = substr_count($result, 'Seeded:');
                            $output .= " 成功执行了 {$seededCount} 个数据填充文件\n";
                        }

                        // 检查是否有错误
                        if (strpos($result, 'ERROR') !== false ||
                            strpos($result, 'Error') !== false ||
                            strpos($result, 'Failed') !== false ||
                            strpos($result, 'Exception') !== false ||
                            strpos($result, 'SQLSTATE') !== false) {
                            $output .= "  执行过程中出现错误，请检查上述输出信息\n\n";
                            $output .= "🔄 <button onclick='location.reload()' class='btn btn-warning'>重新执行</button>\n";
                        } else {
                            $output .= " 初始数据填充成功！\n\n";
                            $output .= " <button onclick='window.location.href=\"?step=storage_link\"' class='btn btn-success'>下一步：创建存储链接</button>\n";
                        }

                    } catch (Exception $e) {
                        $output = '填充初始数据失败: ' . $e->getMessage();
                        $output .= "\n\n 可能的原因:\n";
                        $output .= "- 数据库表结构不匹配\n";
                        $output .= "- Seeder文件不存在或有语法错误\n";
                        $output .= "- 外键约束冲突\n";
                        $output .= "- PHP 环境不支持 shell_exec 函数\n\n";
                        $output .= "🔄 <button onclick='location.reload()' class='btn btn-warning'>重新执行</button>\n";
                        throw $e;
                    }
                    break;

                case 'storage_link':
                    if (function_exists('shell_exec') || function_exists('exec')) {
                        $artisanCommand = "cd " . escapeshellarg(BASE_PATH) . " && php artisan storage:link 2>&1";
                        $result = shell_exec($artisanCommand);

                        // 检查各种结果
                        if (strpos($result, 'link has been connected') !== false) {
                            $output = '成功';
                        } elseif (strpos($result, 'link already exists') !== false) {
                            $output = '成功（链接已存在）';
                        } elseif (strpos($result, 'Call to undefined function') !== false) {
                            // Laravel命令失败，因为exec函数被禁用，尝试直接使用PHP函数
                            if (function_exists('symlink')) {
                                @mkdir(BASE_PATH . '/storage/app/public', 0755, true);
                                $success = @symlink(BASE_PATH . '/storage/app/public', BASE_PATH . '/public/storage');
                                $output = $success ? '成功（使用PHP函数创建）' : '失败，请手动创建: ln -s ' . BASE_PATH . '/storage/app/public ' . BASE_PATH . '/public/storage';
                            } else {
                                $output = 'PHP函数也不可用，请手动创建: ln -s ' . BASE_PATH . '/storage/app/public ' . BASE_PATH . '/public/storage';
                            }
                        } else {
                            $output = '失败: ' . trim($result);
                        }
                    } elseif (function_exists('symlink')) {
                        $output = @symlink(BASE_PATH . '/storage/app/public', BASE_PATH . '/public/storage') ? '成功' : '失败，请手动创建: ln -s ' . BASE_PATH . '/storage/app/public ' . BASE_PATH . '/public/storage';
                    } else {
                        $output = '请手动运行: ln -s ' . BASE_PATH . '/storage/app/public ' . BASE_PATH . '/public/storage';
                    }
                    break;

                case 'create_lock':
                    // 创建安装锁定文件
                    $lockFile = BASE_PATH . '/' . INSTALL_LOCK_FILE;
                    $storageDir = BASE_PATH . '/storage';
                    
                    // 确保 storage 目录存在
                    if (!is_dir($storageDir)) {
                        mkdir($storageDir, 0755, true);
                    }
                    
                    $lockData = [
                        'installed_at' => date('Y-m-d H:i:s'),
                        'version' => '1.0.0',
                        'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'server' => gethostname(),
                        'php_version' => PHP_VERSION
                    ];
                    
                    $result = file_put_contents($lockFile, json_encode($lockData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    
                    if ($result === false) {
                        throw new Exception('无法创建安装锁定文件，请检查 storage 目录权限');
                    }
                    
                    // 验证文件是否成功创建
                    if (!file_exists($lockFile)) {
                        throw new Exception('安装锁定文件创建失败');
                    }
                    
                    $output = '安装锁定文件已创建：' . $lockFile;
                    break;

                default:
                    echo json_encode(['success' => false, 'error' => '未知的action: ' . $action]);
                    exit;
            }

            echo json_encode([
                'success' => true,
                'output' => $output,
                'step_index' => $stepIndex
            ]);
        } catch (Throwable $e) {
            // 确保无论什么异常都返回JSON格式
            $errorMessage = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();

            // 记录错误到输出中（用于调试）
            error_log("Install script error: {$errorMessage} in {$errorFile}:{$errorLine}");

            // 确保响应头已设置
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }

            echo json_encode([
                'success' => false,
                'error' => $errorMessage,
                'debug' => [
                    'file' => basename($errorFile),
                    'line' => $errorLine,
                    'type' => get_class($e)
                ]
            ]);
        }
        exit;
    }

    // 处理传统表单提交
    try {
        switch ($currentStep) {
            case 'config':
                // 验证数据库连接
                $dbHost = $_POST['db_host'] ?? '127.0.0.1';
                $dbPort = $_POST['db_port'] ?? '3306';
                $dbName = $_POST['db_name'] ?? '';
                $dbUser = $_POST['db_user'] ?? '';
                $dbPass = $_POST['db_pass'] ?? '';

                if (empty($dbName) || empty($dbUser)) {
                    throw new Exception('数据库名称和用户名不能为空');
                }

                // 测试数据库连接和创建数据库
                $connectionSuccess = false;
                try {
                    // 首先连接到MySQL服务器（不指定数据库）
                    $pdo = new PDO(
                        "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4",
                        $dbUser,
                        $dbPass,
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );

                    // 创建数据库（如果不存在）
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                    // 验证数据库是否可以访问
                    $pdo->exec("USE `{$dbName}`");

                    $connectionSuccess = true;

                } catch (PDOException $e) {
                    $connectionSuccess = false;
                    $connectionError = '数据库连接或创建失败：' . $e->getMessage();
                }

                if (!$connectionSuccess) {
                    // 数据库连接失败，保存表单数据到会话，让页面重新显示时保留用户输入
                    if (!isset($_SESSION)) {
                        session_start();
                    }
                    $_SESSION['db_config_form'] = [
                        'db_host' => $dbHost,
                        'db_port' => $dbPort,
                        'db_name' => $dbName,
                        'db_user' => $dbUser,
                        'db_pass' => $dbPass
                    ];
                    $error = $connectionError;
                    // 不继续执行保存配置的逻辑
                    break;
                }

                // 连接成功，继续保存配置
                $output = "数据库连接和创建测试成功\n";
                $output .= "- 连接到MySQL服务器: {$dbHost}:{$dbPort}\n";
                $output .= "- 用户名: {$dbUser}\n";
                $output .= "- 数据库: {$dbName} (已创建/验证)\n";

                // 使用 setEnvValue 函数直接写入 .env 文件
                try {
                    setEnvValue('DB_CONNECTION', 'mysql');
                    setEnvValue('DB_HOST', $dbHost);
                    setEnvValue('DB_PORT', $dbPort);
                    setEnvValue('DB_DATABASE', $dbName);
                    setEnvValue('DB_USERNAME', $dbUser);
                    setEnvValue('DB_PASSWORD', $dbPass);

                    // 同时设置其他必要的配置
                    setEnvValue('APP_ENV', 'local');
                    setEnvValue('APP_DEBUG', 'true');
                    setEnvValue('LOG_CHANNEL', 'stack');
                    setEnvValue('QUEUE_CONNECTION', 'sync');
                    setEnvValue('SESSION_DRIVER', 'file');
                    setEnvValue('CACHE_DRIVER', 'file');
                    setEnvValue('MAIL_MAILER', 'log');

                } catch (Exception $e) {
                    throw new Exception('写入配置文件失败：' . $e->getMessage());
                }

                // 清除配置缓存
                if (file_exists(BASE_PATH . '/bootstrap/cache/config.php')) {
                    unlink(BASE_PATH . '/bootstrap/cache/config.php');
                }

                // 直接重定向到安装步骤，避免页面刷新问题
                header('Location: ?step=install');
                exit;

            case 'install':
                // 安装步骤通过AJAX执行，不需要POST处理
                // 如果直接访问install步骤，重定向到config步骤
                if (!isset($_GET['step']) || $_GET['step'] !== 'install') {
                    header('Location: ?step=config');
                    exit;
                }
                break;

            case 'app_config':
                // 处理应用配置 - 让错误自然冒泡
                $appName = trim($_POST['app_name'] ?? '');
                $appEnv = trim($_POST['app_env'] ?? '');
                $appDebug = trim($_POST['app_debug'] ?? '');
                $appUrl = trim($_POST['app_url'] ?? '');

                // 保存配置到.env文件 - 让setEnvValue函数的异常自然冒泡
                setEnvValue('APP_NAME', $appName);
                setEnvValue('APP_ENV', $appEnv);
                setEnvValue('APP_DEBUG', $appDebug);
                setEnvValue('APP_URL', $appUrl);

                // 清除配置缓存
                if (file_exists(BASE_PATH . '/bootstrap/cache/config.php')) {
                    unlink(BASE_PATH . '/bootstrap/cache/config.php');
                }

                // 重定向到完成页面
                header('Location: ?step=complete');
                exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// 检查安装是否已完成
$isInstalled = false;
$lockFile = BASE_PATH . '/' . INSTALL_LOCK_FILE;

// 首先检查安装锁文件
if (file_exists($lockFile)) {
    $isInstalled = true;
} elseif (file_exists(BASE_PATH . '/.env')) {
    // 如果没有锁文件，检查数据库表
    try {
        $app->loadEnvironmentFrom('.env');
        $app->make('config')->clear();

        if (! empty(config('app.key'))) {
            try {
                Illuminate\Support\Facades\DB::connection()->getPdo();
                $tables = Illuminate\Support\Facades\DB::select('SHOW TABLES');
                if (\count($tables) > 0) {
                    $isInstalled = true;
                }
            } catch (Exception $e) {
                // 数据库未配置或连接失败
            }
        }
    } catch (Exception $e) {
        // 忽略错误
    }
}

// 检查是否需要重新安装
$forceReinstall = isset($_GET['reinstall']) && $_GET['reinstall'] === '1';

// 如果已安装且不是完成页面或应用配置页面，且没有强制重新安装，重定向到完成页面
if ($isInstalled && $currentStep !== 'complete' && $currentStep !== 'app_config' && !$forceReinstall) {
    $currentStep = 'complete';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YFSNS 安装向导</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f5f5f5;min-height:100vh;padding:20px}
        .container{max-width:900px;margin:0 auto;background:white;border-radius:10px;box-shadow:0 10px 40px rgba(0,0,0,.2);overflow:hidden}
        .header{background:white;color:#333;padding:30px;text-align:center;border-bottom:1px solid #e0e0e0}
        .header h1{font-size:28px;margin-bottom:10px}
        .header p{opacity:.9}
        .steps{display:flex;background:#f5f5f5;border-bottom:1px solid #ddd}
        .step-item{flex:1;padding:15px;text-align:center;border-right:1px solid #ddd}
        .step-item:last-child{border-right:none}
        .step-item.active{background:white;color:#667eea;font-weight:bold}
        .step-item.completed{color:#4caf50}
        .step-item .icon{font-size:24px;display:block;margin-bottom:5px}
        .content{padding:40px}
        .form-group{margin-bottom:20px}
        .form-group label{display:block;margin-bottom:8px;font-weight:600;color:#333}
        .form-group input,.form-group select{width:100%;padding:12px;border:1px solid #ddd;border-radius:5px;font-size:14px;transition:border-color .3s}
        .form-group input:focus,.form-group select:focus{outline:none;border-color:#667eea}
        .btn{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;padding:12px 30px;border-radius:5px;font-size:16px;cursor:pointer;transition:transform .2s}
        .btn:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(102,126,234,.4)}
        .btn:active{transform:translateY(0)}
        .btn-secondary{background:#6c757d}
        .btn-success{background:linear-gradient(135deg,#28a745 0%,#20c997 100%)}
        .btn-warning{background:linear-gradient(135deg,#ffc107 0%,#fd7e14 100%)}
        .alert{padding:15px;border-radius:5px;margin-bottom:20px}
        .alert-error{background:#fee;color:#c33;border:1px solid #fcc}
        .alert-success{background:#efe;color:#3c3;border:1px solid #cfc}
        .alert-info{background:#eef;color:#33c;border:1px solid #ccf}
        .check-list{list-style:none}
        .check-list li{padding:10px;border-bottom:1px solid #eee}
        .check-list li:last-child{border-bottom:none}
        .check-list .status{float:right;font-weight:bold}
        .check-list .status.ok{color:#4caf50}
        .check-list .status.fail{color:#f44336}
        .output{background:#f5f5f5;padding:15px;border-radius:5px;font-family:'Courier New',monospace;font-size:12px;max-height:400px;overflow-y:auto;white-space:pre-wrap;word-wrap:break-word}
        .warning{background:#fff3cd;border:1px solid #ffc107;color:#856404;padding:15px;border-radius:5px;margin-bottom:20px}
        .actions{display:flex;gap:10px;margin-top:20px}
        .form-control{background:#f8f9fa;border:1px solid #ced4da;border-radius:.375rem;padding:.375rem .75rem}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> YFSNS 安装向导</h1>
            <p>按照以下步骤完成应用程序的安装</p>
        </div>
        
        <div class="steps">
            <?php foreach ($steps as $key => $step) { ?>
                <div class="step-item <?php echo $key === $currentStep ? 'active' : '' ?> <?php echo array_search($key, array_keys($steps)) < array_search($currentStep, array_keys($steps)) ? 'completed' : '' ?>">
                    <span class="icon"><?php echo $step['icon'] ?></span>
                    <span><?php echo $step['name'] ?></span>
                </div>
            <?php } ?>
        </div>
        
        <div class="content">
            <?php
            // 检查安装锁文件
            $lockFile = BASE_PATH . '/' . INSTALL_LOCK_FILE;
            if (file_exists($lockFile) && $currentStep !== 'complete' && $currentStep !== 'app_config' && !$forceReinstall) {
                $lockData = json_decode(file_get_contents($lockFile), true);
                ?>
                <div class="alert alert-error">
                    <strong>⚠️ 系统已安装！</strong>
                    <p>检测到安装锁定文件 <code>storage/install.lock</code>，系统已完成安装。</p>
                    <?php if (isset($lockData['installed_at'])) { ?>
                        <p><strong>安装时间：</strong> <?php echo htmlspecialchars($lockData['installed_at']); ?></p>
                    <?php } ?>
                    <p>如需重新安装，请先删除安装锁定文件，或访问 <a href="?reinstall=1">?reinstall=1</a> 强制重新安装。</p>
                </div>
            <?php } ?>
            
            <?php if ($error) { ?>
                <div class="alert alert-error">
                    <strong>错误：</strong> <?php echo htmlspecialchars($error) ?>
                </div>
            <?php } ?>
            
            <?php if ($success) { ?>
                <div class="alert alert-success">
                    <strong>成功：</strong> <?php echo htmlspecialchars($success) ?>
                </div>
            <?php } ?>
            
            <?php
            switch ($currentStep) {
                case 'check':
                    // 环境检测
                    $checks = [
                        'PHP 版本' => [
                            'value' => \PHP_VERSION,
                            'status' => version_compare(\PHP_VERSION, '8.2.0') >= 0,
                            'required' => '>= 8.2.0',
                        ],
                        'PDO 扩展' => [
                            'value' => \extension_loaded('pdo') ? '已安装' : '未安装',
                            'status' => \extension_loaded('pdo'),
                        ],
                        'PDO MySQL 扩展' => [
                            'value' => \extension_loaded('pdo_mysql') ? '已安装' : '未安装',
                            'status' => \extension_loaded('pdo_mysql'),
                        ],
                        'MBString 扩展' => [
                            'value' => \extension_loaded('mbstring') ? '已安装' : '未安装',
                            'status' => \extension_loaded('mbstring'),
                        ],
                        'OpenSSL 扩展' => [
                            'value' => \extension_loaded('openssl') ? '已安装' : '未安装',
                            'status' => \extension_loaded('openssl'),
                        ],
                        'JSON 扩展' => [
                            'value' => \extension_loaded('json') ? '已安装' : '未安装',
                            'status' => \extension_loaded('json'),
                        ],
                        'Composer 自动加载' => [
                            'value' => file_exists(BASE_PATH . '/vendor/autoload.php') ? '存在' : '不存在',
                            'status' => file_exists(BASE_PATH . '/vendor/autoload.php'),
                        ],
                        'storage 目录可写' => [
                            'value' => is_writable(BASE_PATH . '/storage') ? '可写' : '不可写',
                            'status' => is_writable(BASE_PATH . '/storage'),
                        ],
                        'bootstrap/cache 目录可写' => [
                            'value' => is_writable(BASE_PATH . '/bootstrap/cache') ? '可写' : '不可写',
                            'status' => is_writable(BASE_PATH . '/bootstrap/cache'),
                        ],
                        'Shell 执行函数' => [
                            'value' => (function_exists('shell_exec') || function_exists('exec')) ? '可用' : '不可用',
                            'status' => function_exists('shell_exec') || function_exists('exec'),
                            'required' => '用于执行Artisan命令',
                        ],
                        'Symlink 函数' => [
                            'value' => function_exists('symlink') ? '可用' : '不可用',
                            'status' => function_exists('symlink'),
                            'required' => '用于创建存储软连接，执行 php artisan storage:link',
                        ],
                    ];

                    $allPassed = true;
                    foreach ($checks as $check) {
                        if (isset($check['status']) && ! $check['status']) {
                            $allPassed = false;

                            break;
                        }
                    }
                    ?>
                    <h2>环境检测</h2>
                    <p>请确保以下环境要求都已满足：</p>
                    
                    <ul class="check-list">
                        <?php foreach ($checks as $name => $check) { ?>
                            <li>
                                <strong><?php echo htmlspecialchars($name) ?></strong>
                                <?php if (isset($check['required'])) { ?>
                                    <span style="color: #999;">(要求: <?php echo htmlspecialchars($check['required']) ?>)</span>
                                <?php } ?>
                                <span class="status <?php echo $check['status'] ? 'ok' : 'fail' ?>">
                                    <?php echo $check['status'] ? '✓' : '✗' ?>
                                </span>
                                <br>
                                <small style="color: #666;"><?php echo htmlspecialchars($check['value']) ?></small>
                            </li>
                        <?php } ?>
                    </ul>
                    
                    <?php if ($allPassed) { ?>
                        <div class="alert alert-success">
                            所有环境检查通过！
                        </div>
                        <div class="actions">
                            <a href="?step=config" class="btn">下一步：配置数据库</a>
                        </div>
                    <?php } else { ?>
                        <div class="alert alert-error">
                            请先解决上述问题，然后刷新页面重新检测。
                        </div>
                    <?php } ?>
                    <?php break; ?>
                    
                <?php case 'config': ?>
                    <p>请填写您的数据库连接信息，系统将自动创建配置文件。</p>

                    <?php
                    // 检查是否已经配置过
                    $envPath = BASE_PATH . '/.env';
                    $hasExistingConfig = false;
                    $existingConfig = [];

                    if (file_exists($envPath)) {
                        try {
                            $envContent = file_get_contents($envPath);
                            if ($envContent !== false) {
                                $lines = array_filter(array_map('trim', explode("\n", $envContent)));
                                foreach ($lines as $line) {
                                    if (empty($line) || str_starts_with($line, '#')) {
                                        continue;
                                    }
                                    $pos = strpos($line, '=');
                                    if ($pos !== false) {
                                        $key = trim(substr($line, 0, $pos));
                                        $value = trim(substr($line, $pos + 1));
                                        $value = trim($value, '"\'');
                                        $existingConfig[$key] = $value;
                                    }
                                }
                                $hasExistingConfig = !empty($existingConfig['DB_HOST']) || !empty($existingConfig['DB_DATABASE']);
                            }
                        } catch (Exception $e) {
                            // 忽略读取错误
                        }
                    }

                    // 设置默认值
                    // 优先使用会话中保存的失败数据，然后是现有配置，最后是默认值
                    $savedFormData = null;
                    if (isset($_SESSION) && isset($_SESSION['db_config_form'])) {
                        $savedFormData = $_SESSION['db_config_form'];
                        unset($_SESSION['db_config_form']); // 使用后清除
                    }

                    $dbHost = $savedFormData['db_host'] ?? $existingConfig['DB_HOST'] ?? DEFAULT_DB_HOST;
                    $dbPort = $savedFormData['db_port'] ?? $existingConfig['DB_PORT'] ?? DEFAULT_DB_PORT;
                    $dbName = $savedFormData['db_name'] ?? $existingConfig['DB_DATABASE'] ?? '';
                    $dbUser = $savedFormData['db_user'] ?? $existingConfig['DB_USERNAME'] ?? '';
                    $dbPass = $savedFormData['db_pass'] ?? $existingConfig['DB_PASSWORD'] ?? '';

                    ?>

                    <?php
                    if ($hasExistingConfig) {
                        $configItems = [
                            "数据库主机：{$dbHost}",
                            "端口：{$dbPort}",
                            "数据库名：{$dbName}",
                            "用户名：{$dbUser}",
                            "密码：" . ($dbPass ? '已设置' : '未设置')
                        ];
                        echo generateAlert('success', '已检测到现有配置：', '您可以修改这些配置或直接继续下一步。', $configItems);
                    } else {
                        echo generateAlert('info', '首次配置：', '请填写您的数据库连接信息，系统将自动创建 .env 配置文件。');
                    }
                    ?>

                        <form method="post">
                            <?php
                            echo generateFormGroup(
                                '数据库主机',
                                '<input type="text" name="db_host" value="' . htmlspecialchars($dbHost) . '" required>',
                                'MySQL服务器地址'
                            );
                            echo generateFormGroup(
                                '数据库端口',
                                '<input type="text" name="db_port" value="' . htmlspecialchars($dbPort) . '" required>',
                                'MySQL端口号'
                            );
                            echo generateFormGroup(
                                '数据库名称',
                                '<input type="text" name="db_name" value="' . htmlspecialchars($dbName) . '" required>',
                                '如果数据库不存在，将自动创建'
                            );
                            echo generateFormGroup(
                                '数据库用户名',
                                '<input type="text" name="db_user" value="' . htmlspecialchars($dbUser) . '" required>',
                                '具有创建数据库权限的用户'
                            );
                            echo generateFormGroup(
                                '数据库密码',
                                '<input type="password" name="db_pass" value="' . htmlspecialchars($dbPass) . '">',
                                '数据库用户密码'
                            );
                            ?>


                            <div class="actions">
                                <a href="?step=check" class="btn btn-secondary">上一步</a>
                                <button type="submit" class="btn"><?php echo $hasExistingConfig ? '更新配置并测试连接' : '保存配置并测试连接'; ?></button>
                            </div>
                        </form>
                    <?php break; ?>
                    
                <?php case 'install': ?>
                    <?php
                    $canExecuteShell = function_exists('shell_exec') || function_exists('exec');
                    ?>

                    <div class="warning">
                        <strong> 注意：</strong> 此过程将清空数据库并重新安装所有数据！
                    </div>

                    <?php if (!$canExecuteShell): ?>
                    <div class="alert alert-warning">
                        <strong>⚠️ 服务器不支持自动执行命令</strong>
                        <p>检测到您的服务器禁用了shell_exec和exec函数，无法自动执行Artisan命令。</p>
                        <p>您需要手动执行以下命令，或联系服务器管理员启用相应函数。</p>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace; font-size: 14px;">
                            <strong>请在项目根目录执行：</strong><br>
                            <code style="color: #d73a49;">php artisan migrate:fresh --force</code><br>
                            <code style="color: #d73a49;">php artisan key:generate --force</code><br>
                            <code style="color: #d73a49;">php artisan db:seed --force</code><br>
                            <code style="color: #d73a49;">php artisan storage:link</code><br>
                            <code style="color: #005cc5;">echo "installed_at='$(date '+%Y-%m-%d %H:%M:%S')'" > storage/install.lock</code>
                        </div>
                        <p><strong>执行完成后，点击"我已手动执行完成"按钮继续。</strong></p>
                    </div>
                    <?php endif; ?>

                    <div id="install-progress" style="margin: 20px 0;">
                        <div id="progress-output" style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 12px; max-height: 500px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; border: 1px solid #e0e0e0;"></div>
                        <div id="progress-bar" style="width: 100%; height: 20px; background: #f0f0f0; border-radius: 10px; margin: 10px 0; overflow: hidden;">
                            <div id="progress-fill" style="width: 0%; height: 100%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); transition: width 0.3s;"></div>
                        </div>
                        <div id="current-step" style="text-align: center; font-weight: bold; color: #667eea;"></div>
                    </div>

                    <div class="actions">
                        <a href="?step=config" class="btn btn-secondary">上一步</a>
                        <?php if ($canExecuteShell): ?>
                            <button type="button" id="start-install" class="btn" onclick="startInstallation(event); return false;">开始安装</button>
                        <?php else: ?>
                            <button type="button" id="manual-confirm" class="btn btn-warning" onclick="confirmManualExecution(); return false;">我已手动执行完成</button>
                        <?php endif; ?>
                        <button type="button" id="next-step" class="btn" style="display: none;" onclick="window.location.href = '?step=app_config'; return false;">下一步：配置应用信息</button>
                    </div>

                    <script>
                    // 辅助函数：生成状态消息
                    function addStatusMessage(message, color = 'black') {
                        return `<p style="color: ${color}; margin: 5px 0;">${message}</p>`;
                    }

                    // 辅助函数：启用/禁用安装按钮
                    function setInstallButton(enabled, text = '开始安装') {
                        var btn = document.getElementById('start-install');
                        if (btn) {
                            btn.disabled = !enabled;
                            btn.textContent = text;
                        }
                    }

                    // 辅助函数：更新当前步骤显示
                    function updateCurrentStep(text) {
                        var stepDisplay = document.getElementById('current-step');
                        if (stepDisplay) {
                            stepDisplay.textContent = text;
                        }
                    }

                    function confirmManualExecution() {
                        var confirmMessage = '请确认您已经手动执行了所有必要的Artisan命令：\n\n';
                        confirmMessage += '1. php artisan migrate:fresh --force\n';
                        confirmMessage += '2. php artisan key:generate --force\n';
                        confirmMessage += '3. php artisan db:seed --force\n';
                        confirmMessage += '4. php artisan storage:link\n';
                        confirmMessage += '5. 创建了install.lock文件\n\n';
                        confirmMessage += '⚠️ 重要：如果存储链接创建失败，请手动创建符号链接\n\n';
                        confirmMessage += '确定继续吗？';

                        if (confirm(confirmMessage)) {
                            // 显示进度信息
                            var output = document.getElementById('progress-output');
                            var progressFill = document.getElementById('progress-fill');
                            var currentStepDisplay = document.getElementById('current-step');

                            output.innerHTML = addStatusMessage('✓ 检测到手动执行模式', 'green');
                            output.innerHTML += addStatusMessage('✓ 数据库迁移 - 手动执行完成', 'blue');
                            output.innerHTML += addStatusMessage('✓ 应用密钥生成 - 手动执行完成', 'blue');
                            output.innerHTML += addStatusMessage('✓ 数据填充 - 手动执行完成', 'blue');
                            output.innerHTML += addStatusMessage('✓ 存储链接创建 - 手动执行完成', 'blue');
                            output.innerHTML += addStatusMessage('✓ 安装锁定创建 - 手动执行完成', 'blue');
                            output.innerHTML += addStatusMessage('🎉 所有步骤已完成！', 'green');
                            output.innerHTML += '<div style="color: #666; font-size: 12px; margin-top: 10px;">';
                            output.innerHTML += '💡 如果某些步骤实际失败，请检查错误信息并手动修复后重新安装。';
                            output.innerHTML += '</div>';

                            progressFill.style.width = '100%';
                            currentStepDisplay.textContent = '安装完成！';

                            // 隐藏手动确认按钮，显示下一步按钮
                            var manualBtn = document.getElementById('manual-confirm');
                            if (manualBtn) manualBtn.style.display = 'none';

                            document.getElementById('next-step').style.display = 'inline-block';

                            // 自动滚动到底部
                            output.scrollTop = output.scrollHeight;
                        }
                    }

                    function startInstallation(e) {
                        // 阻止默认行为和事件冒泡
                        if (e) {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                        
                        var btn = document.getElementById('start-install');
                        // 防止重复点击
                        if (btn.disabled) {
                            return false;
                        }
                        
                        btn.disabled = true;
                        btn.textContent = '安装中...';
                        // 隐藏下一步按钮
                        var nextBtn = document.getElementById('next-step');
                        if (nextBtn) {
                            nextBtn.style.display = 'none';
                        }

                        const steps = [
                            {name: '测试数据库连接', action: 'test_db' },
                            {name: '执行数据库迁移', action: 'migrate' },
                            {name: '生成应用密钥', action: 'key_generate' },
                            {name: '填充初始数据', action: 'seed' },
                            {name: '创建存储链接', action: 'storage_link' },
                            {name: '创建安装锁定', action: 'create_lock' }
                        ];

                        // 检查是否支持shell执行
                        const canExecuteShell = <?php echo $canExecuteShell ? 'true' : 'false'; ?>;

                        let currentStepIndex = 0;
                        const output = document.getElementById('progress-output');
                        const progressFill = document.getElementById('progress-fill');
                        const currentStepDisplay = document.getElementById('current-step');

                        function executeStep(step) {
                            // 隐藏下一步按钮
                            document.getElementById('next-step').style.display = 'none';
                            currentStepDisplay.textContent = `正在${step.name}...`;
                            // 更新进度条：当前步骤索引+1（因为索引从0开始）
                            progressFill.style.width = `${((currentStepIndex + 1) / steps.length) * 100}%`;

                            // 使用AJAX调用PHP执行命令
                            fetch('', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: 'execute_command=1&action=' + encodeURIComponent(step.action) + '&step_index=' + currentStepIndex
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                        output.innerHTML += addStatusMessage(`✓ ${step.name}完成`, 'green');
                                    if (data.output) {
                                        output.innerHTML += `<pre style="background: transparent; padding: 10px; margin: 5px 0; font-size: 12px; white-space: pre-wrap;">${data.output}</pre>`;
                                    }
                                    // 自动滚动到底部
                                    output.scrollTop = output.scrollHeight;

                                    // 更新进度条和步骤索引
                                    currentStepIndex++;
                                    // 进度条显示已完成步骤的百分比
                                    progressFill.style.width = `${(currentStepIndex / steps.length) * 100}%`;

                                    // 判断是否还有下一步
                                    if (currentStepIndex < steps.length) {
                                        // 还有下一步，自动执行下一步
                                        setTimeout(() => executeStep(steps[currentStepIndex]), 1000);
                                    } else {
                                        // 所有步骤完成，进度条100%
                                        progressFill.style.width = '100%';
                                        currentStepDisplay.textContent = ' 安装完成！';
                                        output.innerHTML += '<p style="color: blue; font-weight: bold; margin: 10px 0;"> 安装完成！系统已锁定以确保安全。</p>';
                                        output.scrollTop = output.scrollHeight;
                                        // 隐藏"开始安装"按钮
                                        var startBtn = document.getElementById('start-install');
                                        if (startBtn) {
                                            startBtn.style.display = 'none';
                                        }
                                        // 显示"下一步"按钮，点击后跳转到完成页面
                                        document.getElementById('next-step').style.display = 'inline-block';
                                        document.getElementById('next-step').textContent = '下一步';
                                    }
                                } else {
                                    // 检查是否是shell_exec错误
                                    if (data.error && data.error.includes('shell_exec')) {
                                        output.innerHTML += `<p style="color: orange; margin: 5px 0;">⚠️ ${step.name}失败: 服务器不支持自动执行命令</p>`;
                                        output.innerHTML += `<div style="background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 12px;">
                                            <strong>请手动执行以下命令：</strong><br>
                                            <code style="background: #f8f9fa; padding: 2px 4px; border-radius: 3px;">cd ${projectRoot} && php artisan ${step.action === 'migrate' ? 'migrate:fresh --force' : step.action === 'key_generate' ? 'key:generate --force' : step.action === 'seed' ? 'db:seed --force' : step.action === 'storage_link' ? 'storage:link' : 'migrate:fresh --force'}</code><br>
                                            <small>执行完成后，刷新页面重试或点击"我已手动执行完成"按钮。</small>
                                        </div>`;
                                        setInstallButton(true, '继续安装');
                                        updateCurrentStep('等待手动执行命令');
                                    }
                                    // 检查是否是存储链接权限错误
                                    else if (data.error && (data.error.includes('符号链接') || data.error.includes('storage') || data.error.includes('权限'))) {
                                        output.innerHTML += `<p style="color: orange; margin: 5px 0;">⚠️ ${step.name}失败: 权限或符号链接问题</p>`;
                                        output.innerHTML += `<div style="background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 12px;">
                                            <strong>存储链接创建失败，请手动执行：</strong><br>
                                            <code style="background: #f8f9fa; padding: 2px 4px; border-radius: 3px;">sudo ln -s ${projectRoot}/storage/app/public ${projectRoot}/public/storage</code><br>
                                            <small>如果没有sudo权限，尝试：</small><br>
                                            <code style="background: #f8f9fa; padding: 2px 4px; border-radius: 3px;">ln -s ${projectRoot}/storage/app/public ${projectRoot}/public/storage</code><br>
                                            <small>创建完成后，刷新页面继续安装。</small>
                                        </div>`;
                                        setInstallButton(true, '继续安装');
                                        currentStepDisplay.textContent = '等待手动创建存储链接';
                                    } else {
                                        output.innerHTML += addStatusMessage(`✗ ${step.name}失败: ${data.error}`, 'red');
                                        output.scrollTop = output.scrollHeight;
                                        setInstallButton(true, '重试安装');
                                        updateCurrentStep('安装失败，请检查错误信息');
                                    }
                                }
                            })
                            .catch(error => {
                                output.innerHTML += addStatusMessage(`✗ 网络错误: ${error.message}`, 'red');
                                output.scrollTop = output.scrollHeight;
                                setInstallButton(true, '重试安装');
                                updateCurrentStep('网络错误，请重试');
                            });
                        }

                        // 开始执行第一步
                        executeStep(steps[0]);
                        
                        return false;
                    }
                    </script>
                    <?php break; ?>

                <?php case 'app_config': ?>
                    <h2>📝 应用配置</h2>
                    <div class="alert alert-info">
                        <p>请填写应用的基本信息，这些信息将用于配置您的站点。</p>
                    </div>

                    <?php
                    // 获取当前配置作为默认值
                    $currentAppName = getenv('APP_NAME') ?: DEFAULT_APP_NAME;
                    // 应用配置步骤强制默认为生产环境，让用户明确选择
                    $currentAppEnv = DEFAULT_APP_ENV; // 强制默认为生产环境
                    $currentAppDebug = getenv('APP_DEBUG') ?: DEFAULT_APP_DEBUG;
                    $currentAppUrl = getenv('APP_URL') ?: 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                    ?>

                    <form method="POST" action="?step=app_config">
                        <?php
                        echo generateFormGroup(
                            '站点名称 *',
                            '<input type="text" id="app_name" name="app_name" value="' . htmlspecialchars($currentAppName) . '" required>',
                            '显示在浏览器标题和站点名称中的应用名称'
                        );
                        echo generateFormGroup(
                            '运行环境 *',
                            '<select id="app_env" name="app_env" required>
                                <option value="production" ' . ($currentAppEnv === 'production' ? 'selected' : '') . '>生产环境 (Production)</option>
                                <option value="local" ' . ($currentAppEnv === 'local' ? 'selected' : '') . '>开发环境 (Local)</option>
                            </select>',
                            '生产环境会优化性能并隐藏调试信息'
                        );
                        echo generateFormGroup(
                            '调试模式 *',
                            '<select id="app_debug" name="app_debug" required>
                                <option value="false" ' . ($currentAppDebug === 'false' ? 'selected' : '') . '>关闭 (推荐生产环境)</option>
                                <option value="true" ' . ($currentAppDebug === 'true' ? 'selected' : '') . '>开启 (开发环境)</option>
                            </select>',
                            '开启调试模式会显示详细错误信息'
                        );
                        echo generateFormGroup(
                            '站点URL *',
                            '<input type="url" id="app_url" name="app_url" value="' . htmlspecialchars($currentAppUrl) . '" required>',
                            '您的站点完整URL，例如：http://example.com'
                        );
                        ?>

                        <div class="actions">
                            <a href="?step=install" class="btn btn-secondary">上一步</a>
                            <button type="submit" class="btn btn-success">保存配置并完成安装</button>
                        </div>
                    </form>
                    <?php break; ?>

                <?php case 'complete': ?>
                    <h2> 安装完成！</h2>
                    <div class="alert alert-success">
                        <p>应用程序已成功安装！</p>
                        <?php
                        $lockFile = BASE_PATH . '/' . INSTALL_LOCK_FILE;
                        if (file_exists($lockFile)) {
                            $lockData = json_decode(file_get_contents($lockFile), true);
                            echo '<p style="margin-top: 10px;"><strong>安装锁定文件已创建：</strong> <code>storage/install.lock</code></p>';
                            if (isset($lockData['installed_at'])) {
                                echo '<p><strong>安装时间：</strong> ' . htmlspecialchars($lockData['installed_at']) . '</p>';
                            }
                        }
                        ?>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong> 管理员账号信息：</strong>
                        <p style="margin: 10px 0;">
                            <strong>用户名：</strong> <code>admin</code><br>
                            <strong>密码：</strong> <code>password123</code>
                        </p>
                        <p style="margin: 10px 0; color: #d9534f; font-weight: bold;">
                            ⚠️ 重要：登录后台后请立刻修改密码！
                        </p>
                    </div>
                    
                    <div class="warning">
                        <strong> 安全提示：</strong>
                        <p>为了安全起见，请立即删除此安装文件 <code>install.php</code></p>
                        <p>您可以通过以下方式删除：</p>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li>通过 FTP/SFTP 客户端删除</li>
                            <li>通过 SSH 执行：<code>rm storage/install.lock</code></li>
                            <li>点击下面的按钮自动删除（需要文件有写权限）</li>
                        </ul>
                    </div>
                    
                    <div class="actions">
                        <a href="/" class="btn">访问首页</a>
                        <a href="/admin" class="btn">进入后台</a>
                        <a href="?reinstall=1" class="btn btn-secondary" onclick="return confirm('确定要重新安装吗？这将覆盖现有数据！')">重新安装</a>
                        <?php if (is_writable(__FILE__)) { ?>
                            <a href="?delete=1" class="btn btn-secondary" onclick="return confirm('确定要删除安装文件吗？')">删除安装文件</a>
                        <?php } ?>
                    </div>
                    <?php break; ?>
            <?php } ?>
        </div>
    </div>
    
    <?php
    // 处理删除安装文件
    if (isset($_GET['delete']) && $_GET['delete'] === '1' && is_writable(__FILE__)) {
        unlink(__FILE__);
        echo '<script>alert("安装文件已删除！"); window.location.href = "/";</script>';
        exit;
    }
?>
</body>
</html>

