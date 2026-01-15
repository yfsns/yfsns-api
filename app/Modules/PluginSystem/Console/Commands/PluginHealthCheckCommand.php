<?php

namespace App\Modules\PluginSystem\Console\Commands;

use App\Modules\PluginSystem\Services\Checks\PluginHealthMonitorService;
use Illuminate\Console\Command;

/**
 * 插件健康检查命令
 */
class PluginHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:health-check
                            {--plugin=* : 指定要检查的插件名称}
                            {--format=text : 输出格式 (text, json)}
                            {--clear-cache : 清除健康检查缓存}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检查插件健康状态';

    protected PluginHealthMonitorService $healthMonitor;

    /**
     * Create a new command instance.
     */
    public function __construct(PluginHealthMonitorService $healthMonitor)
    {
        parent::__construct();
        $this->healthMonitor = $healthMonitor;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $format = $this->option('format');
        $clearCache = $this->option('clear-cache');
        $specificPlugins = $this->option('plugin');

        // 清除缓存
        if ($clearCache) {
            $this->healthMonitor->clearHealthCache();
            $this->info('健康检查缓存已清除');
        }

        // 执行健康检查
        if (!empty($specificPlugins)) {
            // 检查指定的插件
            $results = [];
            foreach ($specificPlugins as $pluginName) {
                $results[$pluginName] = $this->healthMonitor->checkPluginHealth($pluginName);
            }
        } else {
            // 执行完整检查
            $this->info('开始执行插件健康检查...');
            $results = $this->healthMonitor->performFullHealthCheck()['plugins'];
        }

        // 输出结果
        $this->outputResults($results, $format);

        return 0;
    }

    /**
     * 输出结果
     */
    protected function outputResults(array $results, string $format): void
    {
        if ($format === 'json') {
            $this->outputJson($results);
        } else {
            $this->outputText($results);
        }
    }

    /**
     * 输出文本格式
     */
    protected function outputText(array $results): void
    {
        $this->line('插件健康检查结果');
        $this->line('==================');

        $healthyCount = 0;
        $unhealthyCount = 0;

        foreach ($results as $pluginName => $health) {
            $status = $this->getStatusIcon($health['status']);
            $this->line("{$status} {$pluginName}: {$health['status']}");

            if (!empty($health['issues'])) {
                foreach ($health['issues'] as $issue) {
                    $this->line("    {$issue['check']}: {$issue['status']}");
                    if (isset($issue['details']['errors'])) {
                        foreach ($issue['details']['errors'] as $error) {
                            $this->line("    - {$error}");
                        }
                    }
                }
            }

            if ($health['status'] === 'healthy') {
                $healthyCount++;
            } else {
                $unhealthyCount++;
            }
        }

        $this->line('');
        $this->line("总结: 健康 {$healthyCount}, 不健康 {$unhealthyCount}");
    }

    /**
     * 输出JSON格式
     */
    protected function outputJson(array $results): void
    {
        $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * 获取状态图标
     */
    protected function getStatusIcon(string $status): string
    {
        return match ($status) {
            'healthy' => '',
            'warning' => '',
            'unhealthy' => '',
            'error' => '',
            default => '',
        };
    }
}
