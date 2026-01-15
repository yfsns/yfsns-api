<?php

namespace App\Modules\PluginSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PluginSystem\Contracts\PluginInterface;
use App\Modules\PluginSystem\Services\PluginConfigManagerService;
use App\Modules\PluginSystem\Services\PluginListService;
use App\Modules\PluginSystem\Services\PluginInstallerService;
use App\Modules\PluginSystem\Services\PluginManagerService;
use App\Modules\PluginSystem\Services\PluginDiscoveryService;
use App\Modules\PluginSystem\Contracts\PluginSecurityCheckerInterface;
use App\Modules\PluginSystem\Models\PluginInstallation;
use App\Modules\PluginSystem\Http\Resources\PluginConfigCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 插件状态管理控制器
 *
 * 提供统一的插件状态管理API：
 * - 安装/卸载：处理数据库迁移和基础数据
 * - 启用/禁用：处理路由注册和运行时状态
 */
class PluginController extends Controller
{
    protected PluginConfigManagerService $configManager;
    protected PluginManagerService $manager;
    protected PluginInstallerService $installer;
    protected PluginDiscoveryService $discoveryService;

    public function __construct(
        PluginConfigManagerService $configManager,
        PluginManagerService $manager,
        PluginInstallerService $installer,
        PluginDiscoveryService $discoveryService
    ) {
        $this->configManager = $configManager;
        $this->manager = $manager;
        $this->installer = $installer;
        $this->discoveryService = $discoveryService;
    }

    /**
     * 获取插件列表
     */
    public function index(): JsonResponse
    {
        $plugins = $this->manager->getPluginList();
        return response()->json([
            'code' => 200,
            'message' => '获取插件列表成功',
            'data' => $plugins,
        ], 200);
    }



    /**
     * 启用插件
     */
    public function enable(string $pluginName): JsonResponse
    {
        $result = $this->manager->enablePlugin($pluginName);

        if (! $result['success']) {
            return response()->json([
                'code' => 400,
                'message' => $result['message'],
                'data' => null,
            ], 400);
        }

        return response()->json([
            'code' => 200,
            'message' => '插件启用成功',
            'data' => $result,
        ], 200);
    }

    /**
     * 禁用插件
     */
    public function disable(string $pluginName): JsonResponse
    {
        $result = $this->manager->disablePlugin($pluginName);

        if (! $result['success']) {
            return response()->json([
                'code' => 400,
                'message' => $result['message'],
                'data' => null,
            ], 400);
        }

        return response()->json([
            'code' => 200,
            'message' => '插件禁用成功',
            'data' => $result,
        ], 200);
    }

    /**
     * 执行插件安全检查
     */
    public function securityCheck(string $pluginName): JsonResponse
    {
        $result = $this->manager->performSecurityCheck($pluginName);

        if ($result['valid']) {
            return response()->json([
                'code' => 200,
                'message' => '插件安全检查通过',
                'data' => $result,
            ], 200);
        }

        return response()->json([
            'code' => 400,
            'message' => '插件安全检查失败',
            'data' => $result,
        ], 400);
    }

    /**
     * 安装插件
     */
    public function install(string $pluginName): JsonResponse
    {
        $result = $this->installer->installPlugin($pluginName);

        if ($result['success']) {
            return response()->json([
                'code' => 200,
                'message' => '插件安装成功',
                'data' => $result,
            ], 200);
        }

        return response()->json([
            'code' => 400,
            'message' => $result['message'],
            'data' => null,
        ], 400);
    }

    /**
     * 卸载插件
     * 删除插件的所有配置项，并标记为未安装
     */
    public function uninstall(string $pluginName): JsonResponse
    {
        $result = $this->installer->uninstall($pluginName);

        if (! $result['success']) {
            return response()->json([
                'code' => 400,
                'message' => $result['message'],
                'data' => null,
            ], 400);
        }

        return response()->json([
            'code' => 200,
            'message' => '插件卸载成功',
            'data' => null,
        ], 200);
    }

    /**
     * 获取插件配置
     */
    public function getConfig(string $pluginName): JsonResponse
    {
        // 使用模型进行查询，支持作用域
        $pluginInstallation = PluginInstallation::where('plugin_name', $pluginName)
            ->installed()
            ->first();

        if (! $pluginInstallation) {
            return response()->json([
                'code' => 404,
                'message' => '插件未安装',
                'data' => null,
            ], 404);
        }

        $configs = $this->configManager->getPluginConfigs($pluginName);

        // 展平配置数组用于Resource
        $flatConfigs = [];
        foreach ($configs as $groupConfigs) {
            $flatConfigs = array_merge($flatConfigs, $groupConfigs);
        }

        // 获取插件的分组配置
        $pluginClass = "Plugins\\{$pluginName}\\Plugin";
        $groups = [];
        if (class_exists($pluginClass)) {
            try {
                $plugin = app($pluginClass);
                if (method_exists($plugin, 'getConfigSchema')) {
                    $schema = $plugin->getConfigSchema();
                    $groups = $schema['groups'] ?? [];
                }
            } catch (\Exception $e) {
                // 忽略错误，使用默认分组标签
            }
        }

        $collection = new PluginConfigCollection($flatConfigs);
        $result = $collection->toArray(request());

        // 使用中文分组标签
        foreach ($result as &$group) {
            if (isset($groups[$group['name']]) && isset($groups[$group['name']]['label'])) {
                $group['label'] = $groups[$group['name']]['label'];
            }
        }

        return response()->json([
            'code' => 200,
            'message' => '获取插件配置成功',
            'data' => $result,
        ], 200);
    }

    /**
     * 更新插件配置
     */
    public function updateConfig(string $pluginName): JsonResponse
    {
        $data = request()->all();

        // 使用模型进行查询，支持作用域
        $pluginInstallation = PluginInstallation::where('plugin_name', $pluginName)
            ->installed()
            ->first();

        if (! $pluginInstallation) {
            return response()->json([
                'code' => 404,
                'message' => '插件未安装',
                'data' => null,
            ], 404);
        }

        // 更新配置
        $result = $this->configManager->setPluginConfigs($pluginName, $data);

        if (count($result['failed']) > 0) {
            return response()->json([
                'code' => 400,
                'message' => '部分配置更新失败: '.implode(', ', $result['failed']),
                'data' => $result,
            ], 400);
        }

        return response()->json([
            'code' => 200,
            'message' => '插件配置更新成功',
            'data' => $result,
        ], 200);
    }

    /**
     * 执行插件配置按钮操作
     */
    public function executeConfigAction(string $pluginName, Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|string',
            'config_key' => 'required|string',
        ]);

        $action = $request->input('action');
        $configKey = $request->input('config_key');

        // 获取插件实例
        $plugin = $this->getPluginInstance($pluginName);
        if (! $plugin) {
            return response()->json([
                'code' => 404,
                'message' => '插件未找到',
                'data' => null,
            ], 404);
        }

        try {
            // 调用插件的配置操作方法
            if (method_exists($plugin, 'executeConfigAction')) {
                $result = $plugin->executeConfigAction($action, $configKey, $request->all());

                return response()->json([
                    'code' => 200,
                    'message' => '操作执行成功',
                    'data' => $result,
                ], 200);
            }

            return response()->json([
                'code' => 400,
                'message' => '插件不支持此操作',
                'data' => null,
            ], 400);
        } catch (\Exception $e) {
            Log::error("Plugin config action failed: {$pluginName}.{$configKey}.{$action}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'code' => 500,
                'message' => '操作执行失败：'.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 获取配置数据表格数据
     */
    public function getConfigDataTable(string $pluginName, string $tableKey, Request $request): JsonResponse
    {
        // 获取插件实例
        $plugin = $this->getPluginInstance($pluginName);
        if (! $plugin) {
            return response()->json([
                'code' => 404,
                'message' => '插件未找到',
                'data' => null,
            ], 404);
        }

        try {
            // 调用插件的数据表格方法
            if (method_exists($plugin, 'getDataTableData')) {
                $params = $request->all();
                $result = $plugin->getDataTableData($tableKey, $params);

                return response()->json([
                    'code' => 200,
                    'message' => '数据获取成功',
                    'data' => $result,
                ], 200);
            }

            return response()->json([
                'code' => 400,
                'message' => '插件不支持数据表格',
                'data' => null,
            ], 400);
        } catch (\Exception $e) {
            Log::error("Plugin data table fetch failed: {$pluginName}.{$tableKey}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'code' => 500,
                'message' => '数据获取失败：'.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 获取插件实例
     */
    private function getPluginInstance(string $pluginName)
    {
        $pluginClass = "Plugins\\{$pluginName}\\Plugin";
        if (!class_exists($pluginClass)) {
            return null;
        }

        try {
            return app($pluginClass);
        } catch (\Exception $e) {
            Log::error("Failed to instantiate plugin {$pluginName}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 手动发现插件
     */
    public function discover(): JsonResponse
    {
        $result = $this->discoveryService->discoverPlugins();

        if (! $result['success']) {
            return response()->json([
                'code' => 500,
                'message' => $result['message'],
                'data' => null,
            ], 500);
        }

        return response()->json([
            'code' => 200,
            'message' => $result['message'],
            'data' => $result,
        ], 200);
    }

    /**
     * 获取插件发现状态
     */
    public function getDiscoveryStatus(): JsonResponse
    {
        $status = $this->discoveryService->getDiscoveryStatus();

        return response()->json([
            'code' => 200,
            'message' => '获取发现状态成功',
            'data' => $status,
        ], 200);
    }

    /**
     * 重新扫描插件
     */
    public function rescan(): JsonResponse
    {
        // 清除旧的发现记录
        $cleared = $this->discoveryService->clearDiscoveryRecords();

        if (! $cleared) {
            return response()->json([
                'code' => 500,
                'message' => '清除发现记录失败',
                'data' => null,
            ], 500);
        }

        // 重新发现插件
        $result = $this->discoveryService->discoverPlugins();

        if (! $result['success']) {
            return response()->json([
                'code' => 500,
                'message' => $result['message'],
                'data' => null,
            ], 500);
        }

        return response()->json([
            'code' => 200,
            'message' => '重新扫描完成：'.$result['message'],
            'data' => $result,
        ], 200);
    }

    /**
     * 发现单个插件
     */
    public function discoverSingle(string $pluginName): JsonResponse
    {
        $result = $this->discoveryService->discoverPlugin($pluginName);

        if (! $result['success']) {
            return response()->json([
                'code' => 400,
                'message' => $result['message'],
                'data' => null,
            ], 400);
        }

        return response()->json([
            'code' => 200,
            'message' => '插件发现成功',
            'data' => $result,
        ], 200);
    }


}
