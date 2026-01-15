<?php

namespace App\Modules\PluginSystem\Services;

use App\Modules\PluginSystem\Models\PluginInstallation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * 插件发现服务
 *
 * 提供手动插件发现功能：
 * - 扫描plugins目录发现插件
 * - 解析插件信息并注册到数据库
 * - 提供发现状态查询和管理
 */
class PluginDiscoveryService
{
    /**
     * 发现并注册所有插件
     *
     * @return array 发现结果
     */
    public function discoverPlugins(): array
    {
        Log::info('PluginDiscoveryService: Starting manual plugin discovery');

        try {
            // 扫描插件目录
            $discoveredPlugins = $this->scanPluginDirectory();

            if (empty($discoveredPlugins)) {
                Log::info('PluginDiscoveryService: No plugins found');
                return [
                    'success' => true,
                    'discovered' => 0,
                    'failed' => 0,
                    'plugins' => [],
                    'message' => '未发现任何插件'
                ];
            }

            // 注册插件到数据库
            $registrationResult = $this->registerDiscoveredPlugins($discoveredPlugins);

            $result = [
                'success' => true,
                'discovered' => count($discoveredPlugins),
                'registered' => $registrationResult['registered'],
                'failed' => $registrationResult['failed'],
                'plugins' => $discoveredPlugins,
                'registration' => $registrationResult,
                'message' => sprintf(
                    '发现完成：发现%d个插件，成功注册%d个，失败%d个',
                    count($discoveredPlugins),
                    $registrationResult['registered'],
                    $registrationResult['failed']
                )
            ];

            Log::info('PluginDiscoveryService: Discovery completed', [
                'discovered' => $result['discovered'],
                'registered' => $result['registered'],
                'failed' => $result['failed']
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error('PluginDiscoveryService: Discovery failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => '插件发现失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 发现单个插件
     *
     * @param string $pluginName 插件名称
     * @return array 发现结果
     */
    public function discoverPlugin(string $pluginName): array
    {
        Log::info("PluginDiscoveryService: Discovering single plugin: {$pluginName}");

        try {
            $pluginPath = base_path("plugins/{$pluginName}");

            if (!is_dir($pluginPath)) {
                return [
                    'success' => false,
                    'message' => "插件目录不存在: {$pluginPath}"
                ];
            }

            // 分析插件
            $pluginInfo = $this->analyzePlugin($pluginPath, $pluginName);

            if (!$pluginInfo) {
                return [
                    'success' => false,
                    'message' => '插件分析失败，可能是插件结构不完整'
                ];
            }

            // 注册插件
            $registrationResult = $this->registerSinglePlugin($pluginInfo);

            return [
                'success' => $registrationResult['success'],
                'plugin' => $pluginInfo,
                'registration' => $registrationResult,
                'message' => $registrationResult['success']
                    ? '插件发现并注册成功'
                    : '插件发现成功但注册失败: ' . $registrationResult['message']
            ];

        } catch (Exception $e) {
            Log::error("PluginDiscoveryService: Failed to discover plugin {$pluginName}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => '插件发现失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取发现状态
     *
     * @return array 发现状态信息
     */
    public function getDiscoveryStatus(): array
    {
        try {
            // 获取插件目录信息
            $pluginPath = base_path('plugins');
            $directoryExists = is_dir($pluginPath);

            $filesystemPlugins = [];
            if ($directoryExists) {
                $pluginDirs = array_diff(scandir($pluginPath), ['.', '..']);
                foreach ($pluginDirs as $dir) {
                    $pluginFile = $pluginPath . '/' . $dir . '/Plugin.php';
                    $filesystemPlugins[] = [
                        'name' => $dir,
                        'path' => $pluginPath . '/' . $dir,
                        'has_plugin_file' => file_exists($pluginFile),
                        'is_example' => $this->isExamplePlugin($dir)
                    ];
                }
            }

            // 获取数据库中已注册的插件
            $registeredPlugins = PluginInstallation::select([
                'plugin_name',
                'version',
                'installed',
                'enabled',
                'created_at',
                'updated_at'
            ])->get()->toArray();

            // 统计信息
            $stats = [
                'filesystem' => [
                    'total' => count($filesystemPlugins),
                    'valid' => count(array_filter($filesystemPlugins, fn($p) => $p['has_plugin_file'] && !$p['is_example'])),
                    'examples' => count(array_filter($filesystemPlugins, fn($p) => $p['is_example'])),
                    'invalid' => count(array_filter($filesystemPlugins, fn($p) => !$p['has_plugin_file']))
                ],
                'database' => [
                    'total' => count($registeredPlugins),
                    'installed' => count(array_filter($registeredPlugins, fn($p) => $p['installed'])),
                    'enabled' => count(array_filter($registeredPlugins, fn($p) => $p['enabled']))
                ]
            ];

            // 未注册的插件
            $registeredNames = array_column($registeredPlugins, 'plugin_name');
            $unregisteredPlugins = array_filter($filesystemPlugins, function($plugin) use ($registeredNames) {
                return !in_array($plugin['name'], $registeredNames) && $plugin['has_plugin_file'] && !$plugin['is_example'];
            });

            return [
                'filesystem_exists' => $directoryExists,
                'plugin_path' => $pluginPath,
                'filesystem_plugins' => $filesystemPlugins,
                'registered_plugins' => $registeredPlugins,
                'unregistered_plugins' => array_values($unregisteredPlugins),
                'stats' => $stats,
                'last_check' => now()->toISOString()
            ];

        } catch (Exception $e) {
            Log::error('PluginDiscoveryService: Failed to get discovery status: ' . $e->getMessage());
            return [
                'error' => $e->getMessage(),
                'filesystem_exists' => false,
                'stats' => ['filesystem' => ['total' => 0], 'database' => ['total' => 0]]
            ];
        }
    }

    /**
     * 清除发现记录（重置）
     *
     * @return bool 操作结果
     */
    public function clearDiscoveryRecords(): bool
    {
        try {
            // 删除所有未安装的插件记录（只保留已安装的）
            PluginInstallation::where('installed', false)->delete();

            Log::info('PluginDiscoveryService: Discovery records cleared');
            return true;

        } catch (Exception $e) {
            Log::error('PluginDiscoveryService: Failed to clear discovery records: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 扫描插件目录
     *
     * @return array 发现的插件列表
     */
    protected function scanPluginDirectory(): array
    {
        $pluginPath = base_path('plugins');

        if (!is_dir($pluginPath)) {
            return [];
        }

        $discoveredPlugins = [];
        $pluginDirs = array_diff(scandir($pluginPath), ['.', '..']);

        foreach ($pluginDirs as $pluginDir) {
            // 跳过示例插件
            if ($this->shouldSkipPlugin($pluginDir)) {
                continue;
            }

            $pluginInfo = $this->analyzePlugin($pluginPath . '/' . $pluginDir, $pluginDir);
            if ($pluginInfo) {
                $discoveredPlugins[] = $pluginInfo;
            }
        }

        return $discoveredPlugins;
    }

    /**
     * 分析单个插件
     *
     * @param string $pluginPath 插件路径
     * @param string $pluginName 插件名称
     * @return array|null 插件信息
     */
    protected function analyzePlugin(string $pluginPath, string $pluginName): ?array
    {
        $pluginFile = $pluginPath . '/Plugin.php';
        $configFile = $pluginPath . '/config.json';

        if (!file_exists($pluginFile)) {
            return null;
        }

        try {
            // 动态加载插件类
            $this->loadPluginClass($pluginFile, $pluginName);

            // 实例化插件获取信息
            $pluginClass = "Plugins\\{$pluginName}\\Plugin";
            $pluginInstance = new $pluginClass();

            // 解析配置信息
            $config = $this->parsePluginConfig($configFile);

            return [
                'name' => $pluginName,
                'class' => $pluginClass,
                'info' => $pluginInstance->getInfo(),
                'config' => $config,
                'path' => $pluginPath,
                'analyzed_at' => now(),
            ];

        } catch (Exception $e) {
            Log::warning("PluginDiscoveryService: Failed to analyze plugin {$pluginName}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 注册发现的插件到数据库
     *
     * @param array $plugins 插件列表
     * @return array 注册结果
     */
    protected function registerDiscoveredPlugins(array $plugins): array
    {
        $result = [
            'registered' => 0,
            'failed' => 0,
            'results' => []
        ];

        foreach ($plugins as $plugin) {
            $registrationResult = $this->registerSinglePlugin($plugin);
            $result['results'][$plugin['name']] = $registrationResult;

            if ($registrationResult['success']) {
                $result['registered']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * 注册单个插件
     *
     * @param array $pluginInfo 插件信息
     * @return array 注册结果
     */
    protected function registerSinglePlugin(array $pluginInfo): array
    {
        try {
            $existingPlugin = PluginInstallation::where('plugin_name', $pluginInfo['name'])->first();

            if ($existingPlugin) {
                // 插件已存在，只更新版本和类名等信息，不改变安装和启用状态
                $existingPlugin->update([
                    'plugin_class' => $pluginInfo['class'],
                    'version' => $pluginInfo['info']['version'] ?? '1.0.0',
                    'updated_at' => now(),
                ]);
            } else {
                // 插件不存在，创建新记录
                PluginInstallation::create([
                    'plugin_name' => $pluginInfo['name'],
                    'plugin_class' => $pluginInfo['class'],
                    'version' => $pluginInfo['info']['version'] ?? '1.0.0',
                    'installed' => false, // 新发现的插件默认未安装
                    'enabled' => false,   // 新发现的插件默认未启用
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return [
                'success' => true,
                'message' => '插件注册成功'
            ];

        } catch (Exception $e) {
            Log::error("PluginDiscoveryService: Failed to register plugin {$pluginInfo['name']}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '插件注册失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 加载插件类
     *
     * @param string $pluginFile 插件文件路径
     * @param string $pluginName 插件名称
     */
    protected function loadPluginClass(string $pluginFile, string $pluginName): void
    {
        if (!file_exists($pluginFile)) {
            throw new Exception("Plugin file not found: {$pluginFile}");
        }

        // 检查类是否已加载
        $className = "Plugins\\{$pluginName}\\Plugin";
        if (class_exists($className)) {
            return;
        }

        // 动态加载文件
        include_once $pluginFile;

        if (!class_exists($className)) {
            throw new Exception("Plugin class not found after loading: {$className}");
        }
    }

    /**
     * 解析插件配置
     *
     * @param string $configFile 配置文件路径
     * @return array 配置信息
     */
    protected function parsePluginConfig(string $configFile): array
    {
        if (!file_exists($configFile)) {
            return [];
        }

        $config = json_decode(file_get_contents($configFile), true);
        return $config ?: [];
    }

    /**
     * 判断是否应该跳过插件
     *
     * @param string $pluginName 插件名称
     * @return bool 是否跳过
     */
    protected function shouldSkipPlugin(string $pluginName): bool
    {
        // 跳过示例插件
        if (stripos($pluginName, 'example') === 0 || stripos($pluginName, 'test') === 0) {
            return true;
        }

        return false;
    }

    /**
     * 判断是否为示例插件
     *
     * @param string $pluginName 插件名称
     * @return bool 是否为示例插件
     */
    protected function isExamplePlugin(string $pluginName): bool
    {
        return stripos($pluginName, 'example') === 0 || stripos($pluginName, 'test') === 0;
    }
}
