<?php

/**
 * YFSNS社交网络服务系统
 *
 * Copyright (C) 2025 合肥音符信息科技有限公司
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Modules\PluginSystem;

use Exception;

use function extension_loaded;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

use const PHP_VERSION;

abstract class BasePlugin implements \App\Modules\PluginSystem\Contracts\PluginInterface
{
    protected $name;

    protected $version;

    protected $description;

    protected $author;

    protected $enabled = false;

    protected $config = [];

    protected $configSchema = [];

    protected $configValues = [];

    protected $dependencies = [];

    protected $requirements = [];

    public function __construct()
    {
        $this->initialize();
        $this->loadConfig();
        $this->checkRequirements();
    }

    /**
     * 获取插件信息.
     */
    public function getInfo()
    {
        return [
            'name' => $this->name,
            'display_name' => $this->name, // 默认display_name与name相同
            'version' => $this->version,
            'description' => $this->description,
            'author' => $this->author,
            'enabled' => $this->enabled,
            'dependencies' => $this->dependencies,
            'requirements' => $this->requirements,
        ];
    }

    /**
     * 启用插件.
     */
    public function enable(): void
    {
        $this->enabled = true;
        $this->onEnable();
        Log::info("Plugin enabled: {$this->name}");
    }

    /**
     * 禁用插件.
     */
    public function disable(): void
    {
        $this->enabled = false;
        $this->onDisable();
        Log::info("Plugin disabled: {$this->name}");
    }

    /**
     * 安装插件.
     */
    public function install()
    {
        $this->onInstall();
        Log::info("Plugin installed: {$this->name}");

        return true;
    }

    /**
     * 卸载插件.
     */
    public function uninstall()
    {
        $this->onUninstall();
        Log::info("Plugin uninstalled: {$this->name}");

        return true;
    }

    /**
     * 更新插件.
     */
    public function update()
    {
        $this->onUpdate();
        Log::info("Plugin updated: {$this->name}");

        return true;
    }

    /**
     * 获取配置项定义（用于前端动态渲染表单）.
     *
     * 子类可以重写此方法来定义配置项结构
     * 返回格式：
     * [
     *   'groups' => [
     *     [
     *       'key' => 'api',
     *       'label' => 'API配置',
     *       'fields' => [
     *         [
     *           'key' => 'base_url',
     *           'label' => 'API地址',
     *           'type' => 'text',
     *           'default' => 'http://127.0.0.1:8001',
     *           'required' => true,
     *           'description' => '审核服务API基础地址',
     *           'placeholder' => '请输入API地址',
     *           'validation' => 'required|url'
     *         ],
     *         ...
     *       ]
     *     ],
     *     ...
     *   ]
     * ]
     *
     * 支持的字段类型：
     * - text: 文本输入框
     * - number: 数字输入框
     * - textarea: 多行文本
     * - select: 下拉选择
     * - checkbox: 复选框
     * - switch: 开关
     * - password: 密码输入框
     * - url: URL输入框
     * - email: 邮箱输入框
     */
    /**
     * 获取配置模式（支持JSON schema模式）.
     */
    public function getConfigSchema(): array
    {
        return $this->configSchema ?: [
            'fields' => [],
            'groups' => []
        ];
    }

    /**
     * 获取配置值（对于schema模式配置）.
     */
    public function getConfigValues(): array
    {
        return $this->configValues;
    }

    /**
     * 设置配置值（对于schema模式配置）.
     */
    public function setConfigValues(array $values): void
    {
        $this->configValues = $values;
        $this->config = array_merge($this->config, $values);

        // 保存到文件
        $this->saveConfigValues();
    }

    /**
     * 保存配置值到文件.
     */
    protected function saveConfigValues(): void
    {
        if (empty($this->configSchema)) {
            return;
        }

        $valuesPath = base_path("plugins/{$this->name}/config.values.json");

        // 添加更新时间
        $this->configValues['updated_at'] = now()->toISOString();

        $content = json_encode($this->configValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        file_put_contents($valuesPath, $content);
    }

    /**
     * 检查是否是schema模式配置.
     */
    public function hasConfigSchema(): bool
    {
        return !empty($this->configSchema);
    }

    /**
     * 初始化插件.
     */
    abstract protected function initialize();

    /**
     * 插件启用时的回调.
     */
    protected function onEnable(): void
    {
        // 子类可以重写此方法
    }

    /**
     * 插件禁用时的回调.
     */
    protected function onDisable(): void
    {
        // 子类可以重写此方法
    }

    /**
     * 插件安装时的回调.
     */
    protected function onInstall(): void
    {
        // 子类可以重写此方法
    }

    /**
     * 插件卸载时的回调.
     */
    protected function onUninstall(): void
    {
        // 子类可以重写此方法
    }

    /**
     * 插件更新时的回调.
     */
    protected function onUpdate(): void
    {
        // 子类可以重写此方法
    }

    /**
     * 加载插件配置.
     */
    protected function loadConfig(): void
    {
        // 确保config是数组
        if (!is_array($this->config)) {
            $this->config = [];
        }

        // 优先加载PHP配置
        $configPath = base_path("plugins/{$this->name}/config.php");
        if (file_exists($configPath)) {
            $phpConfig = require $configPath;
            if (is_array($phpConfig)) {
                $this->config = array_merge($this->config, $phpConfig);
            }
        }

        // 其次加载.ini配置
        $iniPath = base_path("plugins/{$this->name}/info.ini");
        if (file_exists($iniPath)) {
            $iniConfig = parse_ini_file($iniPath);
            if (is_array($iniConfig)) {
                $this->config = array_merge($this->config, $iniConfig);
            }
        }

        // 最后加载JSON配置
        $jsonPath = base_path("plugins/{$this->name}/config.json");
        if (file_exists($jsonPath)) {
            $jsonContent = file_get_contents($jsonPath);
            $jsonConfig = json_decode($jsonContent, true);

            if (is_array($jsonConfig)) {
                // 检查是否是schema模式配置（包含fields和values字段）
                if (isset($jsonConfig['fields']) && isset($jsonConfig['values'])) {
                    // Schema模式配置
                    $this->configSchema = $jsonConfig;

                    // 加载实际的配置值
                    $valuesPath = base_path("plugins/{$this->name}/config.values.json");
                    if (file_exists($valuesPath)) {
                        $valuesContent = file_get_contents($valuesPath);
                        $this->configValues = json_decode($valuesContent, true) ?? [];
                    } else {
                        // 使用默认值
                        $this->configValues = $jsonConfig['values'] ?? [];
                    }

                    // 将配置值合并到config中，方便访问
                    $this->config = array_merge($this->config, $this->configValues);
                } else {
                    // 普通JSON配置
                    $this->config = array_merge($this->config, $jsonConfig);
                }
            }
        }
    }

    /**
     * 检查插件要求
     */
    protected function checkRequirements(): void
    {
        if (! empty($this->requirements)) {
            foreach ($this->requirements as $requirement) {
                if (! $this->checkRequirement($requirement)) {
                    throw new Exception("Plugin requirement not met: {$requirement}");
                }
            }
        }
    }

    /**
     * 检查单个要求
     */
    protected function checkRequirement($requirement)
    {
        if (strpos($requirement, 'php:') === 0) {
            $version = substr($requirement, 4);

            return version_compare(PHP_VERSION, $version, '>=');
        }

        if (strpos($requirement, 'laravel:') === 0) {
            $version = substr($requirement, 9);

            return version_compare(app()->version(), $version, '>=');
        }

        if (strpos($requirement, 'extension:') === 0) {
            $extension = substr($requirement, 10);

            return extension_loaded($extension);
        }

        return true;
    }

    /**
     * 注册钩子.
     */
    protected function registerHook($hookName, $callback): void
    {
        app('plugin.manager')->registerHook($hookName, $callback);
    }

    /**
     * 注册路由.
     */
    protected function registerRoutes($routes): void
    {
        app('router')->group(['prefix' => 'plugins/' . $this->name], function () use ($routes): void {
            foreach ($routes as $route) {
                $this->addRoute($route);
            }
        });
    }

    /**
     * 添加路由.
     */
    protected function addRoute($route): void
    {
        $method = strtolower($route['method']);
        $path = $route['path'];
        $action = $route['action'];
        $middleware = $route['middleware'] ?? [];

        app('router')->$method($path, $action)->middleware($middleware);
    }

    /**
     * 注册中间件.
     */
    protected function registerMiddleware($name, $class): void
    {
        app('router')->aliasMiddleware($name, $class);
    }

    /**
     * 注册视图.
     */
    protected function registerViews(): void
    {
        $viewPath = base_path("plugins/{$this->name}/views");
        if (is_dir($viewPath)) {
            app('view')->addNamespace($this->name, $viewPath);
        }
    }

    /**
     * 注册语言包.
     */
    protected function registerTranslations(): void
    {
        $langPath = base_path("plugins/{$this->name}/lang");
        if (is_dir($langPath)) {
            app('translator')->addNamespace($this->name, $langPath);
        }
    }

    /**
     * 注册配置.
     */
    protected function registerConfig(): void
    {
        $configPath = base_path("plugins/{$this->name}/config");
        if (is_dir($configPath)) {
            foreach (glob($configPath . '/*.php') as $file) {
                $key = basename($file, '.php');
                config(["{$this->name}.{$key}" => require $file]);
            }
        }
    }

    /**
     * 执行数据库迁移.
     */
    protected function runMigrations(): void
    {
        $migrationPath = base_path("plugins/{$this->name}/database/migrations");
        if (is_dir($migrationPath)) {
            // 这里可以调用Laravel的迁移命令
            // 或者手动执行SQL文件
        }
    }

    /**
     * 执行SQL文件.
     */
    protected function runSqlFile($sqlFile): void
    {
        $sqlPath = base_path("plugins/{$this->name}/{$sqlFile}");
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            $statements = array_filter(array_map('trim', explode(';', $sql)));

            foreach ($statements as $statement) {
                if (! empty($statement)) {
                    DB::unprepared($statement);
                }
            }
        }
    }

    /**
     * 获取配置值
     *
     * @param null|mixed $default
     */
    protected function getConfig($key, $default = null)
    {
        // 优先使用新的配置管理系统
        $configManager = app(\App\Modules\PluginSystem\Services\PluginConfigManager::class);
        $value = $configManager->getPluginConfigValue($this->name, $key, null);
        if ($value !== null) {
            return $value;
        }

        // 回退到旧的配置系统
        return $this->config[$key] ?? $default;
    }

    /**
     * 设置配置值
     */
    protected function setConfig($key, $value): void
    {
        // 使用新的配置管理系统
        $configManager = app(\App\Modules\PluginSystem\Services\PluginConfigManager::class);
        $configManager->setPluginConfigValue($this->name, $key, $value);

        // 同时更新本地配置（向后兼容）
        $this->config[$key] = $value;
    }

    /**
     * 检查表是否存在.
     */
    protected function tableExists($tableName)
    {
        return Schema::hasTable($tableName);
    }

    /**
     * 创建表.
     */
    protected function createTable($tableName, $callback): void
    {
        if (! $this->tableExists($tableName)) {
            Schema::create($tableName, $callback);
        }
    }

    /**
     * 删除表.
     */
    protected function dropTable($tableName): void
    {
        if ($this->tableExists($tableName)) {
            Schema::dropIfExists($tableName);
        }
    }


    /**
     * 获取所有插件配置
     */
    protected function getAllConfigs(): array
    {
        $configManager = app(\App\Modules\PluginSystem\Services\PluginConfigManager::class);
        return $configManager->getPluginConfigs($this->name);
    }
}
