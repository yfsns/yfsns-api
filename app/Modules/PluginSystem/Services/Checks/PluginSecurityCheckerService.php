<?php

namespace App\Modules\PluginSystem\Services\Checks;

use App\Modules\PluginSystem\Contracts\PluginSecurityCheckerInterface;
use Illuminate\Support\Facades\Log;

/**
 * 插件安全检查服务
 *
 * 实现全面的插件安全检查，包括语法检查、文件完整性、权限控制、代码安全等
 */
class PluginSecurityCheckerService implements PluginSecurityCheckerInterface
{
    /**
     * 安全策略配置
     */
    protected array $securityPolicies;

    /**
     * 语法验证器
     */
    protected PluginSyntaxValidatorService $syntaxValidator;

    public function __construct(PluginSyntaxValidatorService $syntaxValidator)
    {
        $this->syntaxValidator = $syntaxValidator;
        $this->securityPolicies = $this->getDefaultSecurityPolicies();
    }

    /**
     * 执行完整的插件安全检查
     */
    public function performSecurityCheck(string $pluginName, string $pluginPath): array
    {
        Log::info("PluginSecurityChecker: Starting comprehensive security check for plugin: {$pluginName}");

        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'checks' => []
        ];

        // 1. 语法检查
        $syntaxCheck = $this->checkSyntax($pluginName, $pluginPath);
        $results['checks']['syntax'] = $syntaxCheck;
        if (!$syntaxCheck['valid']) {
            $results['errors'] = array_merge($results['errors'], $syntaxCheck['errors']);
            $results['valid'] = false;
        }
        $results['warnings'] = array_merge($results['warnings'], $syntaxCheck['warnings'] ?? []);

        // 2. 文件完整性检查
        $fileCheck = $this->checkFileIntegrity($pluginName, $pluginPath);
        $results['checks']['file_integrity'] = $fileCheck;
        if (!$fileCheck['valid']) {
            $results['errors'] = array_merge($results['errors'], $fileCheck['errors']);
            $results['valid'] = false;
        }
        $results['warnings'] = array_merge($results['warnings'], $fileCheck['warnings'] ?? []);

        // 3. 代码安全检查
        $codeCheck = $this->checkCodeSecurity($pluginName, $pluginPath);
        $results['checks']['code_security'] = $codeCheck;
        if (!$codeCheck['valid']) {
            $results['errors'] = array_merge($results['errors'], $codeCheck['errors']);
            $results['valid'] = false;
        }
        $results['warnings'] = array_merge($results['warnings'], $codeCheck['warnings'] ?? []);

        // 3. 权限检查
        $permissionCheck = $this->checkPermissions($pluginName);
        $results['checks']['permissions'] = $permissionCheck;
        if (!$permissionCheck['valid']) {
            $results['errors'] = array_merge($results['errors'], $permissionCheck['errors']);
        }

        Log::info("PluginSecurityChecker: Security check completed for {$pluginName}", [
            'valid' => $results['valid'],
            'error_count' => count($results['errors']),
            'warning_count' => count($results['warnings'])
        ]);

        return $results;
    }

    /**
     * 检查插件语法
     */
    public function checkSyntax(string $pluginName, string $pluginPath): array
    {
        try {
            // 使用语法验证器进行检查
            $syntaxResult = $this->syntaxValidator->validatePlugin($pluginName);

            if ($syntaxResult['valid']) {
                return [
                    'valid' => true,
                    'errors' => [],
                    'warnings' => $syntaxResult['warnings'] ?? []
                ];
            } else {
                return [
                    'valid' => false,
                    'errors' => $syntaxResult['errors'] ?? ['语法检查失败'],
                    'warnings' => $syntaxResult['warnings'] ?? []
                ];
            }
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'errors' => ['语法检查异常: ' . $e->getMessage()],
                'warnings' => []
            ];
        }
    }

    /**
     * 检查插件文件完整性
     */
    public function checkFileIntegrity(string $pluginName, string $pluginPath): array
    {
        $errors = [];
        $warnings = [];

        // 检查必需文件是否存在
        $requiredFiles = $this->securityPolicies['required_files'];
        foreach ($requiredFiles as $file) {
            $filePath = $pluginPath . '/' . $file;
            if (!file_exists($filePath)) {
                $errors[] = "缺少必需文件: {$file}";
            }
        }

        // 检查可疑文件
        $suspiciousFiles = $this->securityPolicies['suspicious_files'];
        $allFiles = $this->getAllFiles($pluginPath);

        foreach ($allFiles as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, $suspiciousFiles)) {
                $warnings[] = "发现可疑文件类型: {$file}";
            }

            // 检查文件大小
            if (filesize($pluginPath . '/' . $file) > $this->securityPolicies['max_file_size']) {
                $warnings[] = "文件过大: {$file}";
            }
        }

        // 检查目录权限
        if (!is_readable($pluginPath)) {
            $errors[] = "插件目录不可读: {$pluginPath}";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * 检查插件权限和访问控制
     */
    public function checkPermissions(string $pluginName, array $pluginInfo = []): array
    {
        $errors = [];
        $warnings = [];

        // 检查插件名称格式
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $pluginName)) {
            $errors[] = "插件名称格式不符合规范: {$pluginName}";
        }

        // 检查插件是否在黑名单中
        if (in_array(strtolower($pluginName), $this->securityPolicies['blacklisted_plugins'])) {
            $errors[] = "插件已被列入黑名单: {$pluginName}";
        }

        // 检查插件描述长度
        $description = $pluginInfo['description'] ?? '';
        if (strlen($description) > $this->securityPolicies['max_description_length']) {
            $warnings[] = "插件描述过长";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * 检查插件依赖关系安全
     */
    public function checkDependencies(string $pluginName, array $dependencies = []): array
    {
        $errors = [];
        $warnings = [];

        // 检查依赖循环
        if ($this->hasCircularDependency($pluginName, $dependencies)) {
            $errors[] = "检测到依赖循环";
        }

        // 检查依赖版本冲突
        foreach ($dependencies as $dep => $version) {
            if (!preg_match('/^[0-9]+\.[0-9]+\.[0-9]+/', $version)) {
                $warnings[] = "依赖版本格式不规范: {$dep}@{$version}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * 检查插件代码安全（静态分析）
     */
    public function checkCodeSecurity(string $pluginName, string $pluginPath): array
    {
        $errors = [];
        $warnings = [];

        // 检查PHP文件
        $phpFiles = $this->getFilesByExtension($pluginPath, 'php');

        foreach ($phpFiles as $file) {
            $content = file_get_contents($pluginPath . '/' . $file);

            // 检查危险函数调用
            $dangerousFunctions = $this->securityPolicies['dangerous_functions'];
            foreach ($dangerousFunctions as $func) {
                if (preg_match("/\b{$func}\s*\(/i", $content)) {
                    $errors[] = "在文件 {$file} 中发现危险函数调用: {$func}";
                }
            }

            // 检查可疑代码模式
            $suspiciousPatterns = $this->securityPolicies['suspicious_patterns'];
            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $warnings[] = "在文件 {$file} 中发现可疑代码模式";
                }
            }

            // 检查文件大小
            if (strlen($content) > $this->securityPolicies['max_php_file_size']) {
                $warnings[] = "PHP文件过大: {$file}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * 检查插件配置安全
     */
    public function checkConfiguration(string $pluginName, array $config = []): array
    {
        $errors = [];
        $warnings = [];

        // 检查敏感配置项
        $sensitiveKeys = $this->securityPolicies['sensitive_config_keys'];
        foreach ($config as $key => $value) {
            if (in_array($key, $sensitiveKeys)) {
                $warnings[] = "发现敏感配置项: {$key}";
            }

            // 检查配置值类型安全
            if (is_string($value) && strlen($value) > $this->securityPolicies['max_config_value_length']) {
                $warnings[] = "配置值过长: {$key}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * 检查插件数据库操作安全
     */
    public function checkDatabaseOperations(string $pluginName, array $migrations = []): array
    {
        $errors = [];
        $warnings = [];

        // 检查迁移文件
        foreach ($migrations as $migration) {
            $content = file_get_contents($migration);

            // 检查危险的数据库操作
            $dangerousOperations = $this->securityPolicies['dangerous_db_operations'];
            foreach ($dangerousOperations as $operation) {
                if (preg_match("/\b{$operation}\b/i", $content)) {
                    $warnings[] = "在迁移文件中发现危险的数据库操作: {$operation}";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * 获取安全检查策略配置
     */
    public function getSecurityPolicies(): array
    {
        return $this->securityPolicies;
    }

    /**
     * 获取默认安全策略配置
     */
    protected function getDefaultSecurityPolicies(): array
    {
        return [
            'required_files' => ['Plugin.php', 'composer.json'],
            'suspicious_files' => ['exe', 'bat', 'cmd', 'sh', 'dll', 'so'],
            'max_file_size' => 10 * 1024 * 1024, // 10MB
            'max_php_file_size' => 1 * 1024 * 1024, // 1MB
            'max_description_length' => 1000,
            'max_config_value_length' => 10000,
            'blacklisted_plugins' => ['test', 'debug', 'admin', 'root'],
            'dangerous_functions' => [
                'eval', 'exec', 'system', 'shell_exec', 'passthru', 'popen',
                'proc_open', 'phpinfo', 'ini_set', 'ini_alter'
            ],
            'dangerous_db_operations' => [
                'DROP TABLE', 'DROP DATABASE', 'TRUNCATE TABLE',
                'DELETE FROM', 'UPDATE.*SET.*=.*'
            ],
            'sensitive_config_keys' => [
                'password', 'secret', 'key', 'token', 'api_key',
                'database_password', 'mail_password'
            ],
            'suspicious_patterns' => [
                '/\$\w+\s*\(\s*\$\w+\s*\)/', // 可疑的函数调用
                '/base64_decode\s*\(/i',     // base64解码
                '/gzinflate\s*\(/i',         // gzip解压
            ]
        ];
    }

    /**
     * 获取目录下所有文件
     */
    protected function getAllFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = str_replace($directory . '/', '', $file->getPathname());
            }
        }

        return $files;
    }

    /**
     * 根据扩展名获取文件
     */
    protected function getFilesByExtension(string $directory, string $extension): array
    {
        $files = $this->getAllFiles($directory);
        return array_filter($files, function($file) use ($extension) {
            return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === $extension;
        });
    }

    /**
     * 检查依赖循环
     */
    protected function hasCircularDependency(string $pluginName, array $dependencies): bool
    {
        // 简化的循环依赖检查
        // 在实际应用中，可能需要更复杂的图算法
        return in_array($pluginName, $dependencies);
    }
}
