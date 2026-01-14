<?php

namespace App\Modules\PluginSystem\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 已启用插件模型
 */
class EnabledPlugin extends Model
{
    protected $table = 'pluginsystem_enabled_plugins';

    protected $fillable = [
        'plugin_name',
        'plugin_class',
        'plugin_info',
        'enabled_at',
    ];

    protected $casts = [
        'plugin_info' => 'array',
        'enabled_at' => 'datetime',
    ];

    /**
     * 获取插件实例
     */
    public function getPluginInstance()
    {
        $className = $this->plugin_class;

        if (!class_exists($className)) {
            // 尝试加载插件文件
            $pluginPath = base_path("plugins/{$this->plugin_name}/Plugin.php");
            if (file_exists($pluginPath)) {
                include_once $pluginPath;
            }
        }

        if (class_exists($className)) {
            return new $className();
        }

        return null;
    }
}
