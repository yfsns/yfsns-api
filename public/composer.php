<?php

/**
 * YFSNS 依赖安装工具
 * 用于在服务器上安装Composer依赖
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查是否是POST请求（用户点击安装按钮）
$installRequested = isset($_POST['install']);

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YFSNS - 依赖安装工具</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .status-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .status-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .status-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .status-value {
            font-size: 0.9rem;
            color: #6b7280;
        }

        .install-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .install-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .install-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .install-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            box-shadow: none;
        }

        .console {
            background: #1f2937;
            color: #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.9rem;
            line-height: 1.5;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
            display: none;
        }

        .console.show {
            display: block;
        }

        .footer {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .footer p {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-info {
            background: #eff6ff;
            border-color: #3b82f6;
            color: #1e40af;
        }

        .alert-success {
            background: #f0fdf4;
            border-color: #10b981;
            color: #047857;
        }

        .alert-warning {
            background: #fffbeb;
            border-color: #f59e0b;
            color: #92400e;
        }

        .alert-error {
            background: #fef2f2;
            border-color: #ef4444;
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>YFSNS 依赖安装工具</h1>
            <p>自动检测环境并安装Composer依赖</p>
        </div>

        <div class="content">
            <?php if ($installRequested): ?>
                <!-- 执行安装结果 -->
                <div class="alert alert-info">
                    <strong>🔄 正在安装依赖...</strong>
                    <p>这可能需要几分钟时间，请耐心等待。</p>
                </div>
            <?php endif; ?>

            <!-- 环境状态 -->
            <div class="status-grid">
                <div class="status-card">
                    <div class="status-icon">🐘</div>
                    <div class="status-title">PHP版本</div>
                    <div class="status-value"><?php echo checkPHPVersion(); ?></div>
                </div>

                <div class="status-card">
                    <div class="status-icon">📦</div>
                    <div class="status-title">Composer状态</div>
                    <div class="status-value"><?php echo checkComposerStatus(); ?></div>
                </div>

                <div class="status-card">
                    <div class="status-icon">💾</div>
                    <div class="status-title">磁盘空间</div>
                    <div class="status-value"><?php echo checkDiskSpace(); ?></div>
                </div>

                <div class="status-card">
                    <div class="status-icon">📁</div>
                    <div class="status-title">Vendor目录</div>
                    <div class="status-value"><?php echo checkVendorStatus(); ?></div>
                </div>
            </div>

            <?php if (!canInstall()): ?>
                <!-- 无法安装 -->
                <div class="alert alert-error">
                    <strong>❌ 无法安装依赖</strong>
                    <p>请检查上述环境状态，确保所有检查都通过后再试。</p>
                </div>
            <?php elseif ($installRequested): ?>
                <!-- 执行安装 -->
                <div id="install-result">
                    <?php echo runComposerInstall(); ?>
                </div>
            <?php else: ?>
                <!-- 安装按钮 -->
                <div class="install-section">
                    <p style="margin-bottom: 20px; color: #6b7280;">
                        环境检查通过，点击下方按钮开始安装Composer依赖
                    </p>
                    <form method="post">
                        <button type="submit" name="install" value="1" class="install-btn">
                            🚀 开始安装依赖
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>⚠️ 安装完成后请立即删除此文件以确保安全</p>
            <p style="margin-top: 5px; font-size: 0.8rem;">
                YFSNS 依赖安装工具 | 版本 1.0
            </p>
        </div>
    </div>

    <?php
    // PHP 函数定义
    function checkPHPVersion() {
        if (version_compare(PHP_VERSION, '8.4.0', '<')) {
            return "❌ " . PHP_VERSION . " (需要 8.4.0+)";
        }
        return "✅ " . PHP_VERSION;
    }

    function checkComposerStatus() {
        $output = [];
        $returnCode = 0;
        exec('composer --version 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            return "❌ 未安装";
        }

        $version = implode("\n", $output);
        return "✅ 已安装";
    }

    function checkDiskSpace() {
        $freeSpace = disk_free_space(__DIR__);
        $freeSpaceGB = round($freeSpace / 1024 / 1024 / 1024, 2);

        if ($freeSpaceGB < 1) {
            return "❌ {$freeSpaceGB}GB (需要 1GB+)";
        }

        return "✅ {$freeSpaceGB}GB";
    }

    function checkVendorStatus() {
        $vendorPath = dirname(__DIR__) . '/vendor';

        if (!is_dir($vendorPath)) {
            return "❌ 不存在";
        }

        if (!is_readable($vendorPath)) {
            return "❌ 不可读";
        }

        // 统计包数量
        $composerLock = dirname(__DIR__) . '/composer.lock';
        if (file_exists($composerLock)) {
            $lockData = json_decode(file_get_contents($composerLock), true);
            $packageCount = count($lockData['packages'] ?? []);
            return "✅ {$packageCount} 个包";
        }

        return "✅ 存在";
    }

    function canInstall() {
        return version_compare(PHP_VERSION, '8.4.0', '>=') &&
               checkComposerStatus() === "✅ 已安装" &&
               !str_contains(checkDiskSpace(), "❌");
    }

    function runComposerInstall() {
        $projectRoot = dirname(__DIR__);
        $command = "cd {$projectRoot} && composer install --no-dev --optimize-autoloader 2>&1";

        set_time_limit(600); // 10分钟超时

        $output = [];
        $returnCode = 0;

        exec($command, $output, $returnCode);

        $result = '<div class="console show">';

        if ($returnCode === 0) {
            $result .= '<div class="alert alert-success">';
            $result .= '<strong>✅ 依赖安装成功！</strong>';
            $result .= '<p>Composer依赖已成功安装，所有包都已就绪。</p>';
            $result .= '</div>';
        } else {
            $result .= '<div class="alert alert-error">';
            $result .= '<strong>❌ 依赖安装失败</strong>';
            $result .= '<p>安装过程中出现错误，请检查以下输出信息。</p>';
            $result .= '</div>';
        }

        $result .= '<pre style="margin-top: 20px;">';
        foreach ($output as $line) {
            $result .= htmlspecialchars($line) . "\n";
        }
        $result .= '</pre></div>';

        return $result;
    }
    ?>
</body>
</html>