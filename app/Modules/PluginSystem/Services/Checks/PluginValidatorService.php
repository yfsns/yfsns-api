<?php

namespace App\Modules\PluginSystem\Services\Checks;

use App\Modules\PluginSystem\Contracts\PluginInterface;
use Illuminate\Support\Facades\Log;

/**
 * 插件验证器
 *
 * 负责在插件启用前进行全面的安全检查和合规性验证
 */
class PluginValidatorService
{
    /**
     * 验证结果
     */
    protected array $validationResults = [];

    /**
     * 验证插件是否可以启用
     */
    public function validateForEnable(string $pluginName, string $pluginPath): array
    {
        // 启用时不检查数据库存在性（因为插件已安装）
        return $this->validatePluginNamingConflicts($pluginName, $pluginPath, false);
    }

    /**
     * 验证插件是否可以安装
     */
    public function validateForInstall(string $pluginName, string $pluginPath): array
    {
        // 安装时不检查数据库存在性（因为前端已过滤）
        return $this->validatePluginNamingConflicts($pluginName, $pluginPath, false);
    }


    /**
     * 验证插件结构
     */
    protected function validatePluginStructure(string $pluginName, string $pluginPath): array
    {
        $errors = [];

        // 检查必需文件
        $requiredFiles = [
            'Plugin.php' => '插件主文件',
            'composer.json' => 'Composer配置文件',
        ];

        foreach ($requiredFiles as $file => $description) {
            if (!file_exists("{$pluginPath}/{$file}")) {
                $errors[] = "缺少必需文件 {$file} ({$description})";
            }
        }

        // 检查插件目录结构
        if (!is_readable($pluginPath)) {
            $errors[] = '插件目录不可读';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * 验证插件信息
     */
    protected function validatePluginInfo(string $pluginName, string $pluginPath): array
    {
        $errors = [];

        try {
            // 尝试加载插件类来获取信息
            $className = "Plugins\\{$pluginName}\\Plugin";
            $pluginFile = "{$pluginPath}/Plugin.php";

            if (!class_exists($className)) {
                include_once $pluginFile;
            }

            if (!class_exists($className)) {
                $errors[] = '无法加载插件类';
                return ['valid' => false, 'errors' => $errors];
            }

            // 检查是否实现PluginInterface
            if (!is_subclass_of($className, PluginInterface::class)) {
                $errors[] = '插件类必须实现 PluginInterface';
                return ['valid' => false, 'errors' => $errors];
            }

            // 创建临时实例来验证信息
            $pluginInstance = new $className();
            $info = $pluginInstance->getInfo();

            // 验证必需字段
            $requiredFields = ['name', 'version', 'description'];
            foreach ($requiredFields as $field) {
                if (!isset($info[$field]) || empty($info[$field])) {
                    $errors[] = "插件信息缺少必需字段: {$field}";
                }
            }

            // 验证插件名称格式
            if (isset($info['name']) && !preg_match('/^[a-zA-Z][a-zA-Z0-9_-]+$/', $info['name'])) {
                $errors[] = '插件名称格式不正确，只能包含字母、数字、下划线和连字符，且必须以字母开头';
            }

            // 验证版本格式
            if (isset($info['version']) && !preg_match('/^\d+\.\d+\.\d+$/', $info['version'])) {
                $errors[] = '插件版本格式不正确，应为 x.y.z 格式';
            }

        } catch (\Throwable $e) {
            $errors[] = '验证插件信息时出现异常: ' . $e->getMessage();
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * 验证插件代码质量
     */
    protected function validatePluginCode(string $pluginName, string $pluginPath): array
    {
        $errors = [];
        $warnings = [];

        // 检查PHP语法
        $pluginFile = "{$pluginPath}/Plugin.php";
        if (file_exists($pluginFile)) {
            $syntaxCheck = $this->checkPhpSyntax($pluginFile);
            if (!$syntaxCheck['valid']) {
                $errors[] = 'PHP语法错误: ' . $syntaxCheck['error'];
            }
        }

        // 检查代码质量问题
        if (file_exists($pluginFile)) {
            $content = file_get_contents($pluginFile);

            // 检查危险函数
            $dangerousFunctions = ['exec', 'shell_exec', 'system', 'passthru', 'eval'];
            foreach ($dangerousFunctions as $func) {
                if (preg_match("/\\b{$func}\\s*\\(/", $content)) {
                    $errors[] = "检测到危险函数调用: {$func}()";
                }
            }

            // 检查文件操作
            $fileOperations = ['unlink', 'rmdir', 'mkdir', 'file_put_contents', 'fwrite'];
            foreach ($fileOperations as $op) {
                if (preg_match("/\\b{$op}\\s*\\(/", $content)) {
                    $warnings[] = "检测到文件操作函数: {$op}()，请确保安全使用";
                }
            }

            // 检查调试代码
            if (preg_match('/\\b(dd|var_dump|print_r)\\s*\\(/', $content)) {
                $warnings[] = '检测到调试代码，建议在生产环境中移除';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * 验证插件安全性（路由和权限）
     */
    protected function validatePluginSecurity(string $pluginName, string $pluginPath): array
    {
        $errors = [];

        try {
            // 检查路由文件
            $routesPath = "{$pluginPath}/routes";
            if (is_dir($routesPath)) {
                $routeFiles = glob("{$routesPath}/*.php");
                foreach ($routeFiles as $routeFile) {
                    $routeErrors = $this->validateRouteFile($routeFile);
                    $errors = array_merge($errors, $routeErrors);
                }
            }

            // 检查权限定义
            $pluginFile = "{$pluginPath}/Plugin.php";
            if (file_exists($pluginFile)) {
                $content = file_get_contents($pluginFile);

                // 检查权限定义格式
                if (preg_match('/permissions.*=>.*\[([^\]]*)\]/s', $content, $matches)) {
                    $permissionsContent = $matches[1];
                    if (!preg_match('/^\s*[\'\"][^\'\"]+\'?\s*=>\s*[\'\"][^\'\"]*[\'\"]/m', $permissionsContent)) {
                        $errors[] = '权限定义格式不正确，应为 [\'permission_key\' => \'permission_description\'] 格式';
                    }
                }
            }

        } catch (\Throwable $e) {
            $errors[] = '验证插件安全性时出现异常: ' . $e->getMessage();
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * 验证路由文件
     */
    protected function validateRouteFile(string $routeFile): array
    {
        $errors = [];

        try {
            $content = file_get_contents($routeFile);

            // 检查路由注册安全性 - 避免使用闭包路由（排除路由组的闭包）
            if (preg_match('/Route::(get|post|put|delete|patch|options)\s*\([^,]+,\s*function\s*\(/s', $content)) {
                $errors[] = '检测到闭包路由，这是不安全的，请使用控制器方法';
            }

            // 检查中间件使用
            if (preg_match('/middleware.*admin/s', $content) && !preg_match('/middleware.*auth/s', $content)) {
                $errors[] = 'admin中间件应该与auth中间件一起使用';
            }

            // 检查路由参数绑定安全性 - 避免不安全的参数使用
            if (preg_match('/\{\s*\$\w+\s*\}/s', $content)) {
                $errors[] = '检测到不安全的路由参数绑定，请使用Laravel的路由模型绑定或显式参数验证';
            }

        } catch (\Throwable $e) {
            $errors[] = '验证路由文件时出现异常: ' . $e->getMessage();
        }

        return $errors;
    }

    /**
     * 验证插件依赖
     */
    protected function validatePluginDependencies(string $pluginName, string $pluginPath): array
    {
        $errors = [];

        try {
            $composerFile = "{$pluginPath}/composer.json";
            if (file_exists($composerFile)) {
                $composerData = json_decode(file_get_contents($composerFile), true);

                if ($composerData === null) {
                    $errors[] = 'composer.json 格式不正确';
                    return ['valid' => false, 'errors' => $errors];
                }

                // 检查Laravel版本兼容性
                if (isset($composerData['require']['laravel/framework'])) {
                    $laravelVersion = $composerData['require']['laravel/framework'];
                    $currentVersion = app()->version();

                    if (!$this->isVersionCompatible($laravelVersion, $currentVersion)) {
                        $errors[] = "Laravel版本不兼容，需要 {$laravelVersion}，当前版本 {$currentVersion}";
                    }
                }

                // 检查PHP版本要求
                if (isset($composerData['require']['php'])) {
                    $phpVersion = $composerData['require']['php'];
                    $currentPhpVersion = PHP_VERSION;

                    if (!$this->isVersionCompatible($phpVersion, $currentPhpVersion)) {
                        $errors[] = "PHP版本不兼容，需要 {$phpVersion}，当前版本 {$currentPhpVersion}";
                    }
                }
            }

        } catch (\Throwable $e) {
            $errors[] = '验证插件依赖时出现异常: ' . $e->getMessage();
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * 验证插件命名冲突
     * @param bool $checkExistence 是否检查数据库存在性（安装时检查，启用时不检查）
     */
    protected function validatePluginNamingConflicts(string $pluginName, string $pluginPath, bool $checkExistence = true): array
    {
        $errors = [];

        try {
            // 获取插件的实际名称（从getInfo()方法）
            $pluginInstance = $this->loadPluginInstanceForValidation($pluginName, $pluginPath);
            if (!$pluginInstance) {
                $errors[] = '无法加载插件实例进行命名验证';
                return ['valid' => false, 'errors' => $errors];
            }

            $pluginInfo = $pluginInstance->getInfo();
            $declaredName = $pluginInfo['name'] ?? '';

            // 检查插件名称是否符合规范
            if (empty($declaredName)) {
                $errors[] = '插件未声明名称（getInfo()方法必须返回name字段）';
                return ['valid' => false, 'errors' => $errors];
            }

            // 检查名称格式是否符合标准（全小写、无分隔符）
            if (!preg_match('/^[a-z]+$/', $declaredName)) {
                $errors[] = "插件名称 '{$declaredName}' 格式不符合规范，应为全小写无分隔符（如：examplestorage）";
            }

            // 检查目录名是否与声明的名称一致
            $directoryName = basename($pluginPath);
            if (strtolower($directoryName) !== $declaredName) {
                $errors[] = "插件目录名 '{$directoryName}' 与声明的名称 '{$declaredName}' 不一致";
            }

            // 检查数据库中是否已存在相同名称的插件（仅在需要时检查）
            if ($checkExistence && $this->pluginNameExistsInDatabase($declaredName)) {
                $errors[] = "插件名称 '{$declaredName}' 已存在于数据库中，可能是重复安装或命名冲突";
            }

            // 检查是否存在相似的名称（标准化后相同）
            $conflictingNames = $this->findConflictingPluginNames($declaredName);
            if (!empty($conflictingNames)) {
                $conflicts = implode(', ', $conflictingNames);
                $errors[] = "插件名称 '{$declaredName}' 与现有插件 {$conflicts} 存在命名冲突（标准化后相同）";
            }

        } catch (\Throwable $e) {
            $errors[] = '命名冲突验证过程中出现异常: ' . $e->getMessage();
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * 加载插件实例用于验证
     */
    protected function loadPluginInstanceForValidation(string $pluginName, string $pluginPath)
    {
        try {
            $className = "Plugins\\{$pluginName}\\Plugin";

            // 检查类是否存在
            if (!class_exists($className)) {
                $pluginFile = "{$pluginPath}/Plugin.php";
                if (file_exists($pluginFile)) {
                    include_once $pluginFile;
                }

                if (!class_exists($className)) {
                    return null;
                }
            }

            // 检查是否实现PluginInterface
            if (!is_subclass_of($className, \App\Modules\PluginSystem\Contracts\PluginInterface::class)) {
                return null;
            }

            return new $className();

        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 检查插件名称是否已存在于数据库
     */
    protected function pluginNameExistsInDatabase(string $pluginName): bool
    {
        try {
            return \DB::table('pluginsystem_plugin_installations')
                ->where('plugin_name', $pluginName)
                ->exists();
        } catch (\Throwable $e) {
            // 如果数据库连接失败，跳过检查
            return false;
        }
    }

    /**
     * 查找可能存在命名冲突的其他插件名称
     */
    protected function findConflictingPluginNames(string $pluginName): array
    {
        try {
            // 标准化当前名称
            $normalizedName = $this->normalizePluginName($pluginName);

            // 查找数据库中所有标准化后相同的名称
            $existingPlugins = \DB::table('pluginsystem_plugin_installations')
                ->pluck('plugin_name')
                ->toArray();

            $conflicts = [];
            foreach ($existingPlugins as $existingName) {
                if ($this->normalizePluginName($existingName) === $normalizedName && $existingName !== $pluginName) {
                    $conflicts[] = $existingName;
                }
            }

            return $conflicts;

        } catch (\Throwable $e) {
            // 如果数据库连接失败，返回空数组
            return [];
        }
    }

    /**
     * 标准化插件名称（去除分隔符，转小写）
     */
    protected function normalizePluginName(string $name): string
    {
        return strtolower(preg_replace('/[_\-\s]/', '', $name));
    }

    /**
     * 检查PHP语法
     */
    protected function checkPhpSyntax(string $filePath): array
    {
        $output = [];
        $returnCode = 0;

        exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            return [
                'valid' => false,
                'error' => implode("\n", $output),
            ];
        }

        return ['valid' => true];
    }

    /**
     * 检查版本兼容性
     */
    protected function isVersionCompatible(string $required, string $current): bool
    {
        try {
            return version_compare($current, $required, '>=');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取最后一次验证结果
     */
    public function getLastValidationResults(): array
    {
        return $this->validationResults;
    }
}
