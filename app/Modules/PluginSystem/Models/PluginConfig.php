<?php

namespace App\Modules\PluginSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PluginConfig extends Model
{
    use HasFactory;

    protected $table = 'pluginsystem_plugin_configs';

    protected $fillable = [
        'plugin_name',
        'config_key',
        'config_label',
        'config_type',
        'config_value',
        'config_default',
        'config_options',
        'config_description',
        'config_group',
        'config_order',
        'is_required',
        'validation_rules',
        // 按钮相关字段
        'button_action',
        'button_variant',
        'button_confirm',
        'button_disabled',
        // 数据表格相关字段
        'data_source',
        'table_columns',
        'table_operations',
        'table_actions',
        'table_filters',
        'table_search',
        'table_pagination',
        'table_default_sort',
        // 只读标识
        'is_readonly',
    ];

    protected $casts = [
        'config_options' => 'array',
        'is_required' => 'boolean',
        'config_order' => 'integer',
        'button_disabled' => 'boolean',
        'is_readonly' => 'boolean',
        // JSON字段自动转换
        'data_source' => 'array',
        'table_columns' => 'array',
        'table_operations' => 'array',
        'table_actions' => 'array',
        'table_filters' => 'array',
        'table_search' => 'array',
        'table_pagination' => 'array',
        'table_default_sort' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeForPlugin($query, string $pluginName)
    {
        return $query->where('plugin_name', $pluginName);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('config_order')->orderBy('config_key');
    }

    public static function getAllForPlugin(string $pluginName): array
    {
        return static::forPlugin($pluginName)
                    ->ordered()
                    ->get()
                    ->map(function ($config) {
                        return [
                            'id' => $config->id,
                            'key' => $config->config_key,
                            'label' => $config->config_label,
                            'type' => $config->config_type,
                            'value' => $config->config_value,
                            'default' => $config->config_default,
                            'options' => $config->config_options ?? [],
                            'description' => $config->config_description,
                            'group' => $config->config_group,
                            'order' => $config->config_order,
                            'required' => (bool) $config->is_required,
                            'validation_rules' => $config->validation_rules,
                            // 按钮相关字段
                            'button_action' => $config->button_action,
                            'button_variant' => $config->button_variant,
                            'button_confirm' => $config->button_confirm,
                            'button_disabled' => $config->button_disabled,
                            // 数据表格相关字段
                            'data_source' => $config->data_source,
                            'table_columns' => $config->table_columns,
                            'table_operations' => $config->table_operations,
                            'table_actions' => $config->table_actions,
                            'table_filters' => $config->table_filters,
                            'table_search' => $config->table_search,
                            'table_pagination' => $config->table_pagination,
                            'table_default_sort' => $config->table_default_sort,
                            // 只读标识
                            'is_readonly' => $config->is_readonly,
                        ];
                    })
                    ->toArray();
    }

    public static function setValuesForPlugin(string $pluginName, array $values): array
    {
        $results = ['success' => [], 'failed' => []];

        foreach ($values as $key => $value) {
            try {
                $config = static::where('plugin_name', $pluginName)
                               ->where('config_key', $key)
                               ->first();

                if ($config) {
                    $config->update(['config_value' => $value]);
                    $results['success'][] = $key;
                } else {
                    $results['failed'][] = $key;
                }
            } catch (\Exception $e) {
                $results['failed'][] = $key;
            }
        }

        return $results;
    }

    public static function resetAllForPlugin(string $pluginName): bool
    {
        try {
            static::forPlugin($pluginName)->each(function ($config) {
                $config->update(['config_value' => $config->config_default]);
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
