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
use Illuminate\Support\ServiceProvider;

use function is_array;

use const PHP_VERSION;

abstract class BaseServiceProviderPlugin extends ServiceProvider implements \App\Modules\PluginSystem\Contracts\PluginInterface
{
    protected $name;

    protected $version;

    protected $description;

    protected $author;

    protected $enabled = false;

    protected $config = [];

    protected $dependencies = [];

    protected $requirements = [];

    /**
     * 构造函数.
     */
    public function __construct($app)
    {
        parent::__construct($app);

        // 在构造函数中初始化插件
        $this->initialize();
        $this->loadConfig();
        $this->checkRequirements();
    }

    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册插件到插件管理器
        if (app()->bound('plugin.manager')) {
            app('plugin.manager')->registerPlugin($this);
        }
    }

    /**
     * 引导服务
     */
    public function boot(): void
    {
        if ($this->enabled) {
            $this->onEnable();
        }
    }

    /**
     * 获取插件信息.
     */
    public function getInfo()
    {
        return [
            'name' => $this->name,
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
        try {
            $this->enabled = true;
            $this->onEnable();
            Log::info("Plugin enabled: {$this->name}");
        } catch (Exception $e) {
            Log::error("Failed to enable plugin: {$this->name}", ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * 禁用插件.
     */
    public function disable(): void
    {
        try {
            $this->enabled = false;
            $this->onDisable();
            Log::info("Plugin disabled: {$this->name}");
        } catch (Exception $e) {
            Log::error("Failed to disable plugin: {$this->name}", ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * 安装插件.
     */
    public function install()
    {
        try {
            $this->onInstall();
            Log::info("Plugin installed: {$this->name}");

            return true;
        } catch (Exception $e) {
            Log::error("Failed to install plugin: {$this->name}", ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * 卸载插件.
     */
    public function uninstall()
    {
        try {
            $this->onUninstall();
            Log::info("Plugin uninstalled: {$this->name}");

            return true;
        } catch (Exception $e) {
            Log::error("Failed to uninstall plugin: {$this->name}", ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * 更新插件.
     */
    public function update()
    {
        try {
            $this->onUpdate();
            Log::info("Plugin updated: {$this->name}");

            return true;
        } catch (Exception $e) {
            Log::error("Failed to update plugin: {$this->name}", ['error' => $e->getMessage()]);

            throw $e;
        }
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
        // 注册路由
        $this->registerRoutes();

        // 注册视图
        $this->registerViews();

        // 注册语言包
        $this->registerTranslations();

        // 注册配置
        $this->registerConfig();

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
        if (! is_array($this->config)) {
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
            $jsonConfig = json_decode(file_get_contents($jsonPath), true);
            if (is_array($jsonConfig)) {
                $this->config = array_merge($this->config, $jsonConfig);
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
     * 注册路由.
     */
    protected function registerRoutes(): void
    {
        $routesPath = base_path("plugins/{$this->name}/routes");
        if (is_dir($routesPath)) {
            foreach (glob($routesPath . '/*.php') as $file) {
                $this->loadRoutesFrom($file);
            }
        }
    }

    /**
     * 注册视图.
     */
    protected function registerViews(): void
    {
        $viewPath = base_path("plugins/{$this->name}/views");
        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, $this->name);
        }
    }

    /**
     * 注册语言包.
     */
    protected function registerTranslations(): void
    {
        $langPath = base_path("plugins/{$this->name}/lang");
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->name);
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
                $this->mergeConfigFrom($file, "{$this->name}.{$key}");
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
            $this->loadMigrationsFrom($migrationPath);
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
        return config("{$this->name}.{$key}", $this->config[$key] ?? $default);
    }

    /**
     * 设置配置值
     */
    protected function setConfig($key, $value): void
    {
        $this->config[$key] = $value;
        config(["{$this->name}.{$key}" => $value]);
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
     * 发布资源文件.
     */
    protected function publishAssets(): void
    {
        $assetsPath = base_path("plugins/{$this->name}/assets");
        if (is_dir($assetsPath)) {
            $this->publishes([
                $assetsPath => public_path("plugins/{$this->name}"),
            ], "plugin-{$this->name}");
        }
    }
}
