<?php

namespace App\Modules\PluginSystem\Services;

use App\Modules\PluginSystem\Models\PluginInstallation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * 插件安装管理器
 *
 * 负责插件的安装操作
 * 处理插件的数据库记录创建和配置注册
 */
class PluginInstallerService
{
    protected PluginConfigManagerService $configManager;

    public function __construct(PluginConfigManagerService $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * 安装插件
     *
     * @param string $pluginName 插件名称
     * @return array 安装结果
     */
    public function installPlugin(string $pluginName): array
    {
        // 检查插件文件是否存在
        $pluginPath = base_path("plugins/{$pluginName}");
        if (!is_dir($pluginPath)) {
            return [
                'success' => false,
                'message' => "插件目录不存在: {$pluginPath}",
            ];
        }

        $pluginFile = $pluginPath . '/Plugin.php';
        if (!file_exists($pluginFile)) {
            return [
                'success' => false,
                'message' => "插件文件不存在: {$pluginFile}",
            ];
        }

        // 检查是否已安装
        $existing = PluginInstallation::where('plugin_name', $pluginName)->first();
        if ($existing && $existing->installed) {
            return [
                'success' => false,
                'message' => '插件已安装',
                'installed' => true,
            ];
        }

        DB::beginTransaction();

        // 注册插件配置
        $configResult = $this->configManager->registerPluginConfigFromJson($pluginName);
        if (!$configResult['success']) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => '配置注册失败: ' . $configResult['message'],
            ];
        }

        // 创建或更新安装记录
        $installation = PluginInstallation::updateOrCreate(
            ['plugin_name' => $pluginName],
            [
                'version' => '1.0.0', // 默认版本
                'installed' => true, // 安装字段设为1
                'installed_at' => now(),
                'enabled' => false,
                'uninstalled_at' => null, // 清除卸载时间
            ]
        );

        DB::commit();

        Log::info("PluginInstaller: Successfully installed plugin {$pluginName}");

        return [
            'success' => true,
            'message' => '插件安装成功',
            'installation' => $installation,
        ];
    }

    /**
     * 卸载插件
     * 删除插件的所有配置项，并将插件状态标记为未安装
     *
     * @param string $pluginName 插件名称
     * @return array 卸载结果
     */
    public function uninstall(string $pluginName): array
    {
        DB::beginTransaction();

        try {
            // 检查插件是否存在
            $pluginPath = base_path("plugins/{$pluginName}");
            if (!is_dir($pluginPath)) {
                return [
                    'success' => false,
                    'message' => "插件目录不存在: {$pluginPath}",
                ];
            }

            // 删除配置
            $result = $this->configManager->removePluginConfigs($pluginName);

            if (!$result) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => '删除配置项失败',
                ];
            }

            // 更新插件安装状态为未安装
            $installation = PluginInstallation::where('plugin_name', $pluginName)->first();
            if ($installation) {
                $installation->update([
                    'installed' => false,
                    'enabled' => false,
                    'uninstalled_at' => now(),
                ]);
            }

            DB::commit();

            Log::info("PluginInstaller: Successfully uninstalled config and updated status for plugin {$pluginName}");

            return [
                'success' => true,
                'message' => '插件配置卸载成功',
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("PluginInstaller: Failed to uninstall config for plugin {$pluginName}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '卸载插件配置失败: ' . $e->getMessage(),
            ];
        }
    }

}
