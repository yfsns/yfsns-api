<?php

namespace App\Modules\PluginSystem\Services;

use App\Modules\PluginSystem\Models\PluginInstallation;
use App\Modules\PluginSystem\Contracts\PluginSecurityCheckerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * 插件管理器服务
 *
 * 提供插件的核心管理功能：
 * - 插件列表获取
 * - 插件启用/禁用
 * - 插件安全检查
 */
class PluginManagerService
{
    protected PluginListService $listService;
    protected PluginSecurityCheckerInterface $securityChecker;

    public function __construct(
        PluginListService $listService,
        PluginSecurityCheckerInterface $securityChecker
    ) {
        $this->listService = $listService;
        $this->securityChecker = $securityChecker;
    }

    /**
     * 获取插件列表
     *
     * @return array 格式化的插件列表
     */
    public function getPluginList(): array
    {
        try {
            return $this->listService->getFormattedPluginList();
        } catch (Exception $e) {
            Log::error("PluginManagerService: Failed to get plugin list: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 启用插件
     *
     * @param string $pluginName 插件名称
     * @return array 操作结果
     */
    public function enablePlugin(string $pluginName): array
    {
        try {
            $installation = PluginInstallation::where('plugin_name', $pluginName)->first();

            if (!$installation) {
                return [
                    'success' => false,
                    'message' => '插件未安装',
                ];
            }

            if ($installation->enabled) {
                return [
                    'success' => false,
                    'message' => '插件已启用',
                    'enabled' => true,
                ];
            }

            $installation->update([
                'enabled' => true,
                'enabled_at' => now(),
            ]);

            Log::info("PluginManagerService: Successfully enabled plugin {$pluginName}");

            return [
                'success' => true,
                'message' => '插件启用成功',
                'state' => 'enabled',
            ];

        } catch (Exception $e) {
            Log::error("PluginManagerService: Failed to enable plugin {$pluginName}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '插件启用失败: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 禁用插件
     *
     * @param string $pluginName 插件名称
     * @return array 操作结果
     */
    public function disablePlugin(string $pluginName): array
    {
        try {
            $installation = PluginInstallation::where('plugin_name', $pluginName)->first();

            if (!$installation) {
                return [
                    'success' => false,
                    'message' => '插件未安装',
                ];
            }

            if (!$installation->enabled) {
                return [
                    'success' => false,
                    'message' => '插件已禁用',
                    'enabled' => false,
                ];
            }

            $installation->update([
                'enabled' => false,
                'disabled_at' => now(),
            ]);

            Log::info("PluginManagerService: Successfully disabled plugin {$pluginName}");

            return [
                'success' => true,
                'message' => '插件禁用成功',
                'state' => 'disabled',
            ];

        } catch (Exception $e) {
            Log::error("PluginManagerService: Failed to disable plugin {$pluginName}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '插件禁用失败: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 执行插件安全检查
     *
     * @param string $pluginName 插件名称
     * @return array 检查结果
     */
    public function performSecurityCheck(string $pluginName): array
    {
        try {
            // 构建插件路径
            $pluginPath = base_path("plugins/{$pluginName}");

            if (!is_dir($pluginPath)) {
                return [
                    'valid' => false,
                    'errors' => ["插件目录不存在: {$pluginPath}"],
                    'warnings' => [],
                    'checks' => [
                        'syntax' => ['valid' => false, 'errors' => ['插件目录不存在'], 'warnings' => []],
                        'file_integrity' => ['valid' => false, 'errors' => ['插件目录不存在'], 'warnings' => []],
                        'code_security' => ['valid' => false, 'errors' => ['插件目录不存在'], 'warnings' => []],
                        'permissions' => ['valid' => false, 'errors' => ['插件目录不存在'], 'warnings' => []],
                    ],
                ];
            }

            $result = $this->securityChecker->performSecurityCheck($pluginName, $pluginPath);

            Log::info("PluginManagerService: Security check completed for plugin {$pluginName}", [
                'valid' => $result['valid'],
                'errors_count' => count($result['errors'] ?? []),
                'warnings_count' => count($result['warnings'] ?? []),
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error("PluginManagerService: Exception during security check for {$pluginName}: " . $e->getMessage());
            return [
                'valid' => false,
                'errors' => ['安全检查过程中发生异常: ' . $e->getMessage()],
                'warnings' => [],
                'checks' => [
                    'syntax' => ['valid' => false, 'errors' => ['异常发生'], 'warnings' => []],
                    'file_integrity' => ['valid' => false, 'errors' => ['异常发生'], 'warnings' => []],
                    'code_security' => ['valid' => false, 'errors' => ['异常发生'], 'warnings' => []],
                    'permissions' => ['valid' => false, 'errors' => ['异常发生'], 'warnings' => []],
                ],
            ];
        }
    }

    /**
     * 批量操作插件
     *
     * @param array $pluginNames 插件名称数组
     * @param string $action 操作类型 (enable|disable)
     * @return array 批量操作结果
     */
    public function batchOperation(array $pluginNames, string $action): array
    {
        $results = [
            'total' => count($pluginNames),
            'success' => 0,
            'failed' => 0,
            'results' => [],
        ];

        foreach ($pluginNames as $pluginName) {
            switch ($action) {
                case 'enable':
                    $result = $this->enablePlugin($pluginName);
                    break;
                case 'disable':
                    $result = $this->disablePlugin($pluginName);
                    break;
                default:
                    $result = [
                        'success' => false,
                        'message' => '不支持的操作类型: ' . $action,
                    ];
            }

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }

            $results['results'][$pluginName] = $result;
        }

        Log::info("PluginManagerService: Batch operation '{$action}' completed: {$results['success']} success, {$results['failed']} failed");

        return $results;
    }

    /**
     * 获取插件状态信息
     *
     * @param string $pluginName 插件名称
     * @return array 插件状态信息
     */
    public function getPluginStatus(string $pluginName): array
    {
        try {
            $installation = PluginInstallation::where('plugin_name', $pluginName)->first();

            if (!$installation) {
                return [
                    'installed' => false,
                    'enabled' => false,
                    'version' => null,
                    'installed_at' => null,
                    'enabled_at' => null,
                    'disabled_at' => null,
                    'updated_at' => null,
                ];
            }

            return [
                'installed' => true,
                'enabled' => $installation->enabled,
                'version' => $installation->version,
                'installed_at' => $installation->installed_at?->toISOString(),
                'enabled_at' => $installation->enabled_at?->toISOString(),
                'disabled_at' => $installation->disabled_at?->toISOString(),
                'updated_at' => $installation->updated_at?->toISOString(),
            ];

        } catch (Exception $e) {
            Log::error("PluginManagerService: Failed to get plugin status {$pluginName}: " . $e->getMessage());
            return [
                'installed' => false,
                'enabled' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
