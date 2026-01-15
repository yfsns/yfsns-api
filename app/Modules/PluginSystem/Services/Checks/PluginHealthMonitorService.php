<?php

namespace App\Modules\PluginSystem\Services\Checks;

use App\Modules\PluginSystem\Contracts\PluginInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 插件健康监控器
 *
 * 监控插件运行状态，定期检查插件健康状况
 */
class PluginHealthMonitorService
{
    protected Checks\PluginSyntaxValidatorService $syntaxValidator;

    const CACHE_KEY = 'plugin_health_status';

    const CACHE_TTL = 3600; // 1小时

    public function __construct(Checks\PluginSyntaxValidatorService $syntaxValidator)
    {
        $this->syntaxValidator = $syntaxValidator;
    }

    /**
     * 执行完整的健康检查
     */
    public function performFullHealthCheck(): array
    {
        Log::info('PluginHealthMonitor: Starting full health check');

        $startTime = microtime(true);
        $results = [
            'timestamp' => now()->toISOString(),
            'plugins' => [],
            'summary' => [
                'total_plugins' => 0,
                'healthy_plugins' => 0,
                'unhealthy_plugins' => 0,
                'check_duration' => 0
            ],
            'system_status' => 'healthy'
        ];

        // 获取所有插件目录
        $pluginPath = base_path('plugins');
        if (!is_dir($pluginPath)) {
            $results['summary']['check_duration'] = microtime(true) - $startTime;
            return $results;
        }

        $pluginDirs = glob("$pluginPath/*", GLOB_ONLYDIR);
        $results['summary']['total_plugins'] = count($pluginDirs);

        foreach ($pluginDirs as $pluginDir) {
            $pluginName = basename($pluginDir);
            $pluginHealth = $this->checkPluginHealth($pluginName);

            $results['plugins'][$pluginName] = $pluginHealth;

            if ($pluginHealth['status'] === 'healthy') {
                $results['summary']['healthy_plugins']++;
            } else {
                $results['summary']['unhealthy_plugins']++;
                $results['system_status'] = 'warning'; // 有一个不健康的插件就标记为警告
            }
        }

        $results['summary']['check_duration'] = round(microtime(true) - $startTime, 3);

        // 如果有太多不健康的插件，标记为错误状态
        if ($results['summary']['unhealthy_plugins'] > $results['summary']['total_plugins'] * 0.5) {
            $results['system_status'] = 'error';
        }

        // 缓存结果
        $this->cacheHealthResults($results);

        Log::info('PluginHealthMonitor: Health check completed', [
            'duration' => $results['summary']['check_duration'],
            'healthy' => $results['summary']['healthy_plugins'],
            'unhealthy' => $results['summary']['unhealthy_plugins']
        ]);

        return $results;
    }

    /**
     * 检查单个插件健康状态
     */
    public function checkPluginHealth(string $pluginName): array
    {
        $health = [
            'plugin_name' => $pluginName,
            'status' => 'unknown',
            'checks' => [],
            'last_checked' => now()->toISOString(),
            'issues' => []
        ];

        try {
            // 1. 语法检查
            $syntaxCheck = $this->checkSyntaxHealth($pluginName);
            $health['checks']['syntax'] = $syntaxCheck;

            // 2. 加载检查
            $loadCheck = $this->checkLoadHealth($pluginName);
            $health['checks']['loading'] = $loadCheck;

            // 3. 运行时检查
            $runtimeCheck = $this->checkRuntimeHealth($pluginName);
            $health['checks']['runtime'] = $runtimeCheck;

            // 4. 性能检查
            $performanceCheck = $this->checkPerformanceHealth($pluginName);
            $health['checks']['performance'] = $performanceCheck;

            // 确定整体状态
            $health['status'] = $this->determineOverallStatus($health['checks']);
            $health['issues'] = $this->collectIssues($health['checks']);

        } catch (\Throwable $e) {
            $health['status'] = 'error';
            $health['issues'][] = [
                'type' => 'exception',
                'message' => $e->getMessage(),
                'severity' => 'critical'
            ];
            Log::error("PluginHealthMonitor: Exception during health check for {$pluginName}: " . $e->getMessage());
        }

        return $health;
    }

    /**
     * 检查语法健康
     */
    protected function checkSyntaxHealth(string $pluginName): array
    {
        $startTime = microtime(true);

        try {
            $validation = $this->syntaxValidator->validatePlugin($pluginName);

            $duration = round(microtime(true) - $startTime, 3);

            if ($validation['valid']) {
                return [
                    'status' => 'healthy',
                    'duration' => $duration,
                    'details' => [
                        'warnings_count' => count($validation['warnings']),
                        'warnings' => $validation['warnings']
                    ]
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'duration' => $duration,
                    'details' => [
                        'errors' => $validation['errors']
                    ]
                ];
            }

        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'duration' => round(microtime(true) - $startTime, 3),
                'details' => [
                    'exception' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * 检查加载健康
     */
    protected function checkLoadHealth(string $pluginName): array
    {
        // 在简化实现中，我们不进行实际的插件加载测试
        // 只返回健康状态，因为插件状态由数据库管理
        return [
            'status' => 'healthy',
            'duration' => 0.001,
            'details' => [
                'message' => 'Plugin loading managed by database state'
            ]
        ];
    }

    /**
     * 检查运行时健康
     */
    protected function checkRuntimeHealth(string $pluginName): array
    {
        // 这里可以检查插件是否正常运行
        // 比如检查插件的服务是否注册，路由是否正常等

        // 暂时返回健康状态
        return [
            'status' => 'healthy',
            'duration' => 0.001,
            'details' => [
                'message' => 'Runtime check not implemented yet'
            ]
        ];
    }

    /**
     * 检查性能健康
     */
    protected function checkPerformanceHealth(string $pluginName): array
    {
        // 这里可以检查插件的性能指标
        // 比如加载时间，内存使用等

        return [
            'status' => 'healthy',
            'duration' => 0.001,
            'details' => [
                'message' => 'Performance check not implemented yet'
            ]
        ];
    }

    /**
     * 确定整体状态
     */
    protected function determineOverallStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        if (in_array('error', $statuses)) {
            return 'error';
        }

        if (in_array('unhealthy', $statuses)) {
            return 'unhealthy';
        }

        if (in_array('warning', $statuses)) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * 收集问题
     */
    protected function collectIssues(array $checks): array
    {
        $issues = [];

        foreach ($checks as $checkName => $checkResult) {
            if ($checkResult['status'] !== 'healthy') {
                $issues[] = [
                    'check' => $checkName,
                    'status' => $checkResult['status'],
                    'details' => $checkResult['details'] ?? []
                ];
            }
        }

        return $issues;
    }

    /**
     * 缓存健康检查结果
     */
    protected function cacheHealthResults(array $results): void
    {
        Cache::put(self::CACHE_KEY, $results, self::CACHE_TTL);
    }

    /**
     * 获取缓存的健康检查结果
     */
    public function getCachedHealthResults(): ?array
    {
        return Cache::get(self::CACHE_KEY);
    }

    /**
     * 获取插件健康状态摘要
     */
    public function getHealthSummary(): array
    {
        $results = $this->getCachedHealthResults();

        if ($results === null) {
            $results = $this->performFullHealthCheck();
        }

        return $results['summary'];
    }

    /**
     * 获取不健康的插件列表
     */
    public function getUnhealthyPlugins(): array
    {
        $results = $this->getCachedHealthResults();

        if ($results === null) {
            $results = $this->performFullHealthCheck();
        }

        $unhealthy = [];

        foreach ($results['plugins'] as $pluginName => $health) {
            if ($health['status'] !== 'healthy') {
                $unhealthy[$pluginName] = $health;
            }
        }

        return $unhealthy;
    }

    /**
     * 清除健康检查缓存
     */
    public function clearHealthCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * 注册定时任务
     */
    public function registerScheduledChecks(): void
    {
        // 这里可以注册Laravel的定时任务
        // 比如每小时检查一次插件健康状态
    }
}
