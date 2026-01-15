<?php

namespace App\Modules\PluginSystem\Services\Checks;

use Illuminate\Support\Facades\Log;

/**
 * 插件语法验证器
 *
 * 负责在插件加载前进行语法检查，确保插件代码没有语法错误
 */
class PluginSyntaxValidatorService
{
    /**
     * 语法检查结果缓存
     */
    protected array $syntaxCheckCache = [];

    /**
     * 检查插件语法
     */
    public function validatePlugin(string $pluginName): array
    {
        // 检查缓存
        $cacheKey = "plugin_syntax_{$pluginName}";
        if (isset($this->syntaxCheckCache[$cacheKey])) {
            return $this->syntaxCheckCache[$cacheKey];
        }

        $pluginPath = base_path("plugins/{$pluginName}");

        if (!is_dir($pluginPath)) {
            $result = [
                'valid' => false,
                'errors' => ["插件目录不存在: {$pluginPath}"],
                'warnings' => [],
                'plugin_name' => $pluginName,
                'checked_at' => now()->toISOString()
            ];
            $this->syntaxCheckCache[$cacheKey] = $result;
            return $result;
        }

        $errors = [];
        $warnings = [];

        // 检查必需文件
        $requiredFiles = $this->checkRequiredFiles($pluginPath);
        if (!$requiredFiles['valid']) {
            $errors = array_merge($errors, $requiredFiles['errors']);
        }

        // 语法检查PHP文件
        $syntaxCheck = $this->checkPhpSyntax($pluginPath);
        if (!$syntaxCheck['valid']) {
            $errors = array_merge($errors, $syntaxCheck['errors']);
        }
        $warnings = array_merge($warnings, $syntaxCheck['warnings']);

        // 检查依赖关系
        $dependencyCheck = $this->checkDependencies($pluginName);
        if (!$dependencyCheck['valid']) {
            $errors = array_merge($errors, $dependencyCheck['errors']);
        }

        // 检查版本兼容性
        $versionCheck = $this->checkVersionCompatibility($pluginName);
        if (!$versionCheck['valid']) {
            $warnings = array_merge($warnings, $versionCheck['warnings']);
        }

        $result = [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'plugin_name' => $pluginName,
            'checked_at' => now()->toISOString()
        ];

        // 缓存结果
        $this->syntaxCheckCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * 检查必需文件
     */
    protected function checkRequiredFiles(string $pluginPath): array
    {
        $requiredFiles = [
            'Plugin.php',
            'composer.json'
        ];

        $errors = [];

        foreach ($requiredFiles as $file) {
            $filePath = "{$pluginPath}/{$file}";
            if (!file_exists($filePath)) {
                $errors[] = "缺少必需文件: {$file}";
            }
        }

        // 检查Plugin.php是否在正确位置
        $pluginFile = "{$pluginPath}/Plugin.php";
        if (file_exists($pluginFile)) {
            $content = file_get_contents($pluginFile);
            $pluginName = basename($pluginPath);

            // 检查命名空间
            if (!str_contains($content, "namespace Plugins\\{$pluginName}")) {
                $errors[] = "Plugin.php命名空间不正确，应为: Plugins\\{$pluginName}";
            }

            // 检查类名
            if (!str_contains($content, "class Plugin")) {
                $errors[] = "Plugin.php必须包含Plugin类";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 检查PHP语法
     */
    protected function checkPhpSyntax(string $pluginPath): array
    {
        $errors = [];
        $warnings = [];

        // 查找所有PHP文件
        $phpFiles = $this->findPhpFiles($pluginPath);

        foreach ($phpFiles as $file) {
            $relativePath = str_replace($pluginPath . '/', '', $file);

            // 检查语法
            $syntaxCheck = $this->checkSingleFileSyntax($file);
            if (!$syntaxCheck['valid']) {
                $errors[] = "{$relativePath}: {$syntaxCheck['error']}";
            }

            // 检查代码质量问题
            $qualityCheck = $this->checkCodeQuality($file);
            $warnings = array_merge($warnings, $qualityCheck['warnings']);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * 查找PHP文件
     */
    protected function findPhpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * 检查单个文件语法
     */
    protected function checkSingleFileSyntax(string $filePath): array
    {
        // 使用PHP的语法检查
        $output = [];
        $returnCode = 0;

        exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            $error = implode("\n", $output);
            return [
                'valid' => false,
                'error' => $error
            ];
        }

        return ['valid' => true];
    }

    /**
     * 检查代码质量
     */
    protected function checkCodeQuality(string $filePath): array
    {
        $warnings = [];
        $content = file_get_contents($filePath);

        // 检查常见问题
        if (str_contains($content, '<?php')) {
            $lines = explode("\n", $content);
            if (count($lines) > 0 && trim($lines[0]) !== '<?php') {
                // 检查是否有多余的空行或BOM
                $firstLine = trim($lines[0]);
                if (empty($firstLine) || ord($firstLine[0]) === 0xEF) {
                    $warnings[] = basename($filePath) . ": 文件开头可能有BOM或空行";
                }
            }
        }

        // 检查关闭标签
        if (str_contains($content, '?>')) {
            $warnings[] = basename($filePath) . ": 包含PHP关闭标签(?>)，建议移除";
        }

        // 检查调试代码
        if (str_contains($content, 'var_dump(') || str_contains($content, 'print_r(') || str_contains($content, 'dd(')) {
            $warnings[] = basename($filePath) . ": 包含调试代码，建议在生产环境中移除";
        }

        return ['warnings' => $warnings];
    }

    /**
     * 检查依赖关系
     */
    protected function checkDependencies(string $pluginName): array
    {
        $errors = [];
        $pluginPath = base_path("plugins/{$pluginName}");
        $composerFile = "{$pluginPath}/composer.json";

        if (file_exists($composerFile)) {
            $composerData = json_decode(file_get_contents($composerFile), true);

            if (isset($composerData['require'])) {
                // 这里可以检查依赖的包是否已安装
                // 暂时只检查格式是否正确
                if (!is_array($composerData['require'])) {
                    $errors[] = "composer.json的require字段格式不正确";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 检查版本兼容性
     */
    protected function checkVersionCompatibility(string $pluginName): array
    {
        $warnings = [];
        $pluginPath = base_path("plugins/{$pluginName}");
        $composerFile = "{$pluginPath}/composer.json";

        if (file_exists($composerFile)) {
            $composerData = json_decode(file_get_contents($composerFile), true);

            // 检查Laravel版本兼容性
            if (isset($composerData['require']['laravel/framework'])) {
                $laravelVersion = $composerData['require']['laravel/framework'];
                $currentVersion = app()->version();

                // 简单的版本检查逻辑
                if (!$this->isVersionCompatible($laravelVersion, $currentVersion)) {
                    $warnings[] = "插件要求的Laravel版本({$laravelVersion})可能与当前版本({$currentVersion})不兼容";
                }
            }
        }

        return [
            'valid' => true, // 版本不匹配只是警告，不阻止加载
            'warnings' => $warnings
        ];
    }

    /**
     * 检查版本兼容性
     */
    protected function isVersionCompatible(string $required, string $current): bool
    {
        // 简单的版本比较，实际应该更复杂
        try {
            return version_compare($current, $required, '>=');
        } catch (\Exception $e) {
            return true; // 如果版本比较失败，默认认为是兼容的
        }
    }

    /**
     * 批量检查插件
     */
    public function validateMultiplePlugins(array $pluginNames): array
    {
        $results = [];

        foreach ($pluginNames as $pluginName) {
            $results[$pluginName] = $this->validatePlugin($pluginName);
        }

        return $results;
    }

    /**
     * 生成验证报告
     */
    public function generateReport(array $validationResults): string
    {
        $report = "插件语法验证报告\n";
        $report .= "生成时间: " . now()->toDateTimeString() . "\n\n";

        $totalPlugins = count($validationResults);
        $validPlugins = 0;
        $invalidPlugins = 0;
        $totalErrors = 0;
        $totalWarnings = 0;

        foreach ($validationResults as $pluginName => $result) {
            if ($result['valid']) {
                $validPlugins++;
            } else {
                $invalidPlugins++;
            }

            $totalErrors += count($result['errors']);
            $totalWarnings += count($result['warnings']);
        }

        $report .= "统计信息:\n";
        $report .= "- 总插件数: {$totalPlugins}\n";
        $report .= "- 有效插件: {$validPlugins}\n";
        $report .= "- 无效插件: {$invalidPlugins}\n";
        $report .= "- 总错误数: {$totalErrors}\n";
        $report .= "- 总警告数: {$totalWarnings}\n\n";

        foreach ($validationResults as $pluginName => $result) {
            $status = $result['valid'] ? '✓ 通过' : '✗ 失败';
            $report .= "插件: {$pluginName} [{$status}]\n";

            if (!empty($result['errors'])) {
                $report .= "错误:\n";
                foreach ($result['errors'] as $error) {
                    $report .= "  - {$error}\n";
                }
            }

            if (!empty($result['warnings'])) {
                $report .= "警告:\n";
                foreach ($result['warnings'] as $warning) {
                    $report .= "  - {$warning}\n";
                }
            }

            $report .= "\n";
        }

        return $report;
    }
}
