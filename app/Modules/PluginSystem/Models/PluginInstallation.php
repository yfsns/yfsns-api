<?php

namespace App\Modules\PluginSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PluginInstallation extends Model
{
    use HasFactory;

    /**
     * 表名
     */
    protected $table = 'pluginsystem_plugin_installations';

    /**
     * 可填充字段
     */
    protected $fillable = [
        'plugin_name',
        'version',
        'installed',
        'enabled',
        'installed_at',
        'enabled_at',
        'disabled_at',
        'uninstalled_at',
    ];

    /**
     * 类型转换
     */
    protected $casts = [
        'installed' => 'boolean',
        'enabled' => 'boolean',
        'installed_at' => 'datetime',
        'enabled_at' => 'datetime',
        'disabled_at' => 'datetime',
        'uninstalled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 作用域：已安装的插件
     */
    public function scopeInstalled($query)
    {
        return $query->where('installed', true);
    }

    /**
     * 作用域：已启用的插件
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * 作用域：按插件名称排序
     */
    public function scopeOrderedByName($query)
    {
        return $query->orderBy('plugin_name');
    }

    /**
     * 作用域：按更新时间排序
     */
    public function scopeLatestUpdated($query)
    {
        return $query->orderBy('updated_at', 'desc');
    }

    /**
     * 查找指定名称的插件
     */
    public static function findByName(string $pluginName): ?self
    {
        return static::where('plugin_name', $pluginName)->first();
    }

    /**
     * 检查插件是否已安装
     */
    public function isInstalled(): bool
    {
        return $this->installed;
    }

    /**
     * 检查插件是否已启用
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * 检查插件是否活跃（已安装且已启用）
     */
    public function isActive(): bool
    {
        return $this->isInstalled() && $this->isEnabled();
    }

    /**
     * 标记为已安装
     */
    public function markAsInstalled(?string $version = null): bool
    {
        $data = [
            'installed' => true,
            'installed_at' => now(),
            'uninstalled_at' => null,
        ];

        if ($version) {
            $data['version'] = $version;
        }

        return $this->update($data);
    }

    /**
     * 标记为已卸载
     */
    public function markAsUninstalled(): bool
    {
        return $this->update([
            'installed' => false,
            'enabled' => false,
            'uninstalled_at' => now(),
            'disabled_at' => now(),
        ]);
    }

    /**
     * 标记为已启用
     */
    public function markAsEnabled(): bool
    {
        return $this->update([
            'enabled' => true,
            'enabled_at' => now(),
            'disabled_at' => null,
        ]);
    }

    /**
     * 标记为已禁用
     */
    public function markAsDisabled(): bool
    {
        return $this->update([
            'enabled' => false,
            'disabled_at' => now(),
        ]);
    }

    /**
     * 切换插件启用状态
     */
    public function toggleEnabled(): array
    {
        if (!$this->isInstalled()) {
            return [
                'success' => false,
                'message' => '插件未安装，无法切换状态',
                'state' => 'toggle_failed'
            ];
        }

        $newState = !$this->enabled;

        if ($newState) {
            // 启用插件
            $success = $this->markAsEnabled();
            $message = '插件启用成功';
            $state = 'enabled';
        } else {
            // 禁用插件
            $success = $this->markAsDisabled();
            $message = '插件禁用成功';
            $state = 'disabled';
        }

        if (!$success) {
            return [
                'success' => false,
                'message' => '状态切换失败',
                'state' => 'toggle_failed'
            ];
        }

        return [
            'success' => true,
            'message' => $message,
            'state' => $state,
            'enabled' => $newState
        ];
    }

    /**
     * 更新插件版本
     */
    public function updateVersion(string $version): bool
    {
        return $this->update(['version' => $version]);
    }

    /**
     * 获取插件状态信息
     */
    public function getStatusInfo(): array
    {
        return [
            'plugin_name' => $this->plugin_name,
            'installed' => $this->isInstalled(),
            'enabled' => $this->isEnabled(),
            'active' => $this->isActive(),
            'version' => $this->version,
            'installed_at' => $this->installed_at,
            'enabled_at' => $this->enabled_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * 获取插件的显示状态
     */
    public function getDisplayStatus(): string
    {
        if ($this->isActive()) {
            return 'active';
        }

        if ($this->isInstalled()) {
            return 'installed';
        }

        return 'not_installed';
    }

    /**
     * 获取插件列表显示的数据格式
     */
    public function toArrayForList(): array
    {
        return [
            'id' => $this->plugin_name,
            'name' => $this->plugin_name,
            'displayName' => $this->plugin_name,
            'description' => '',
            'version' => $this->version ?: '1.0.0',
            'author' => '',
            'status' => $this->getDisplayStatus(),
            'installStatus' => $this->isInstalled() ? 'installed' : 'not_installed',
            'installed' => $this->isInstalled(),
            'enabled' => $this->isEnabled(),
            'hasConfig' => false,
            'category' => null,
            'dependencies' => [],
            'requirements' => [],
            'lastUpdated' => $this->updated_at?->toISOString() ?: '',
            'installedAt' => $this->installed_at?->toISOString() ?: null,
            'enabledAt' => $this->enabled_at?->toISOString() ?: null,
            'disabledAt' => $this->disabled_at?->toISOString() ?: null,
            'discovered' => true,
            'path' => base_path('plugins/' . $this->plugin_name),
        ];
    }
}
