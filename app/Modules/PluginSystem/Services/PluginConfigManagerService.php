<?php

namespace App\Modules\PluginSystem\Services;

use App\Modules\PluginSystem\Models\PluginConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

/**
 * 插件配置管理器
 *
 * 按照标准配置规范提供统一的插件配置管理功能。
 * 只支持 plugins/{PluginName}/config.json 格式的配置文件。
 *
 * 功能特性：
 * - 从标准配置文件加载配置定义
 * - 配置项的增删改查
 * - 配置项的批量操作
 * - 配置验证和分组管理
 * - 自动验证配置文件格式
 */
class PluginConfigManagerService
{
    /**
     * 获取插件的所有配置项
     */
    public function getPluginConfigs(string $pluginName): array
    {
        // 使用模型方法获取配置项
        $configs = PluginConfig::getAllForPlugin($pluginName);

        // 按分组重新组织
        $groupedConfigs = [];
        foreach ($configs as $config) {
            $groupedConfigs[$config['group']][] = $config;
        }

        return $groupedConfigs;
    }

    /**
     * 获取插件配置项的值
     */
    public function getPluginConfigValue(string $pluginName, string $configKey, $default = null)
    {
        $config = DB::table('pluginsystem_plugin_configs')
                   ->where('plugin_name', $pluginName)
                   ->where('config_key', $configKey)
                   ->first();

        return $config ? $config->config_value : $default;
    }

    /**
     * 设置插件配置项的值
     */
    public function setPluginConfigValue(string $pluginName, string $configKey, $value): bool
    {
        $affected = DB::table('pluginsystem_plugin_configs')
                     ->where('plugin_name', $pluginName)
                     ->where('config_key', $configKey)
                     ->update([
                         'config_value' => $value,
                         'updated_at' => now(),
                     ]);

        if ($affected > 0) {
            Log::info("PluginConfigManager: Updated config {$pluginName}.{$configKey} = {$value}");
            return true;
        } else {
            Log::warning("PluginConfigManager: No config found to update: {$pluginName}.{$configKey}");
            return false;
        }
    }

    /**
     * 批量设置插件配置项
     */
    public function setPluginConfigs(string $pluginName, array $configs): array
    {
        return PluginConfig::setValuesForPlugin($pluginName, $configs);
    }

    /**
     * 注册插件配置项
     */
    public function registerPluginConfig(string $pluginName, array $configDefinition): bool
    {
        PluginConfig::updateOrCreate(
            [
                'plugin_name' => $pluginName,
                'config_key' => $configDefinition['key'],
            ],
            [
                'config_label' => $configDefinition['label'] ?? $configDefinition['key'],
                'config_type' => $configDefinition['type'] ?? 'text',
                'config_value' => $configDefinition['value'] ?? $configDefinition['default'] ?? null,
                'config_default' => $configDefinition['default'] ?? null,
                'config_options' => $configDefinition['options'] ?? [],
                'config_description' => $configDefinition['description'] ?? null,
                'config_group' => $configDefinition['group'] ?? 'general',
                'config_order' => $configDefinition['order'] ?? 0,
                'is_required' => $configDefinition['required'] ?? false,
                'validation_rules' => $configDefinition['validation'] ?? null,
                // 按钮相关字段
                'button_action' => $configDefinition['action'] ?? null,
                'button_variant' => $configDefinition['variant'] ?? 'primary',
                'button_confirm' => $configDefinition['confirm'] ?? null,
                'button_disabled' => $configDefinition['disabled'] ?? false,
                // 数据表格相关字段
                'data_source' => $configDefinition['data_source'] ?? null,
                'table_columns' => $configDefinition['columns'] ?? null,
                'table_operations' => $configDefinition['operations'] ?? null,
                'table_actions' => $configDefinition['actions'] ?? null,
                'table_filters' => $configDefinition['filters'] ?? null,
                'table_search' => $configDefinition['search'] ?? null,
                'table_pagination' => $configDefinition['pagination'] ?? null,
                'table_default_sort' => $configDefinition['default_sort'] ?? null,
                // 只读标识
                'is_readonly' => $configDefinition['readonly'] ?? false,
            ]
        );

        Log::info("PluginConfigManager: Registered config {$pluginName}.{$configDefinition['key']}");
        return true;
    }

    /**
     * 批量注册插件配置项
     */
    public function registerPluginConfigs(string $pluginName, array $configDefinitions): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($configDefinitions as $configDef) {
            if ($this->registerPluginConfig($pluginName, $configDef)) {
                $results['success'][] = $configDef['key'];
            } else {
                $results['failed'][] = $configDef['key'];
            }
        }

        return $results;
    }

    /**
     * 删除插件的所有配置项
     */
    public function removePluginConfigs(string $pluginName): bool
    {
        $deleted = PluginConfig::forPlugin($pluginName)->delete();

        Log::info("PluginConfigManager: Removed {$deleted} configs for plugin {$pluginName}");
        return true;
    }

    /**
     * 重置插件配置为默认值
     */
    public function resetPluginConfigs(string $pluginName): bool
    {
        return PluginConfig::resetAllForPlugin($pluginName);
    }


    /**
     * 验证配置文件的结构
     */
    private function validateConfigStructure(?array $config): bool
    {
        if (!$config || !is_array($config)) {
            return false;
        }

        // 检查必需的顶级字段
        if (!isset($config['fields']) || !is_array($config['fields'])) {
            return false;
        }

        // 检查每个字段的结构
        foreach ($config['fields'] as $key => $field) {
            if (!is_array($field)) {
                return false;
            }

            // 必需字段
            if (!isset($field['type']) || !isset($field['label'])) {
                return false;
            }

            // 验证字段类型 - 扩展支持按钮和数据列表
            $validTypes = ['text', 'password', 'select', 'checkbox', 'number', 'email', 'url', 'textarea', 'button', 'data_table'];
            if (!in_array($field['type'], $validTypes)) {
                return false;
            }

            // 如果是select类型，必须有options
            if ($field['type'] === 'select' && (!isset($field['options']) || !is_array($field['options']))) {
                return false;
            }

            // 验证 validation 字段格式 - 必须是字符串
            if (isset($field['validation']) && !is_string($field['validation'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 从标准配置文件注册插件配置到数据库
     */
    public function registerPluginConfigFromJson(string $pluginName): array
    {
        // 加载配置文件
        $configFile = base_path("plugins/{$pluginName}/config.json");

        // 检查文件是否存在
        if (!File::exists($configFile)) {
            Log::info("PluginConfigManager: Config file not found for plugin {$pluginName}: {$configFile}");
            return [
                'success' => false,
                'message' => '配置文件不存在',
                'registered' => 0,
                'failed' => 0
            ];
        }

        $configContent = File::get($configFile);
        $config = json_decode($configContent, true);

        // 验证JSON格式是否正确
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("PluginConfigManager: Invalid JSON in config file for plugin {$pluginName}: " . json_last_error_msg());
            return [
                'success' => false,
                'message' => '配置文件JSON格式错误',
                'registered' => 0,
                'failed' => 0
            ];
        }

        // 验证配置结构
        if (!$this->validateConfigStructure($config)) {
            Log::error("PluginConfigManager: Invalid config structure for plugin {$pluginName}");
            return [
                'success' => false,
                'message' => '配置文件结构不符合规范',
                'registered' => 0,
                'failed' => 0
            ];
        }

        $results = [
            'success' => true,
            'message' => '配置注册成功',
            'registered' => 0,
            'failed' => 0,
            'details' => []
        ];

        // 处理字段配置 - 严格按照规范
        foreach ($config['fields'] as $fieldKey => $fieldConfig) {
            $configDefinition = array_merge($fieldConfig, [
                'key' => $fieldKey,
                'group' => $fieldConfig['group'] ?? 'general',
                'order' => $fieldConfig['order'] ?? 0,
                'required' => $fieldConfig['required'] ?? false,
                'default' => $fieldConfig['default'] ?? null,
                'validation' => $fieldConfig['validation'] ?? null,
                'options' => $fieldConfig['options'] ?? [],
            ]);

            // 如果是data_table类型，从metadata中获取完整配置
            if ($fieldConfig['type'] === 'data_table' && isset($config['metadata']['data_tables'])) {
                $dataTableConfig = collect($config['metadata']['data_tables'])->firstWhere('key', $fieldKey);
                if ($dataTableConfig) {
                    // 合并metadata中的配置，保留fields中的基本信息
                    $configDefinition = array_merge($dataTableConfig, $configDefinition);
                }
            }

            if ($this->registerPluginConfig($pluginName, $configDefinition)) {
                $results['registered']++;
                $results['details']['success'][] = $fieldKey;
            } else {
                $results['failed']++;
                $results['details']['failed'][] = $fieldKey;
            }
        }

        // 设置初始值（如果values部分存在）
        if (isset($config['values']) && is_array($config['values'])) {
            $this->setPluginConfigs($pluginName, $config['values']);
        }

        Log::info("PluginConfigManager: Successfully loaded and registered {$results['registered']} configs for plugin {$pluginName}");

        return $results;
    }


}
