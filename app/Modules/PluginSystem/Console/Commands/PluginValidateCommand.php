<?php

namespace App\Modules\PluginSystem\Console\Commands;

use App\Modules\PluginSystem\Services\Checks\PluginSyntaxValidatorService;
use Illuminate\Console\Command;

/**
 * 插件语法验证命令
 */
class PluginValidateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:validate
                            {--plugin=* : 指定要验证的插件名称}
                            {--format=text : 输出格式 (text, json)}
                            {--save-report : 保存验证报告到文件}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '验证插件语法和结构';

    protected PluginSyntaxValidatorService $syntaxValidator;

    /**
     * Create a new command instance.
     */
    public function __construct(PluginSyntaxValidatorService $syntaxValidator)
    {
        parent::__construct();
        $this->syntaxValidator = $syntaxValidator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $format = $this->option('format');
        $saveReport = $this->option('save-report');
        $specificPlugins = $this->option('plugin');

        // 获取要验证的插件
        if (!empty($specificPlugins)) {
            $pluginNames = $specificPlugins;
        } else {
            // 获取所有插件
            $pluginPath = base_path('plugins');
            if (!is_dir($pluginPath)) {
                $this->error('插件目录不存在');
                return 1;
            }

            $pluginDirs = glob("$pluginPath/*", GLOB_ONLYDIR);
            $pluginNames = array_map('basename', $pluginDirs);
        }

        if (empty($pluginNames)) {
            $this->warn('没有找到插件');
            return 0;
        }

        $this->info("开始验证 " . count($pluginNames) . " 个插件...");
        $results = $this->syntaxValidator->validateMultiplePlugins($pluginNames);

        // 输出结果
        $this->outputResults($results, $format);

        // 保存报告
        if ($saveReport) {
            $this->saveReport($results);
        }

        // 返回退出码
        $hasErrors = false;
        foreach ($results as $result) {
            if (!$result['valid']) {
                $hasErrors = true;
                break;
            }
        }

        return $hasErrors ? 1 : 0;
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
        $this->line('插件语法验证结果');
        $this->line('==================');

        $validCount = 0;
        $invalidCount = 0;
        $totalErrors = 0;
        $totalWarnings = 0;

        foreach ($results as $pluginName => $result) {
            $status = $result['valid'] ? '通过' : '失败';
            $this->line("{$status} {$pluginName}");

            if (!empty($result['errors'])) {
                $this->line('  错误:');
                foreach ($result['errors'] as $error) {
                    $this->line("    - {$error}");
                }
                $totalErrors += count($result['errors']);
            }

            if (!empty($result['warnings'])) {
                $this->line('  警告:');
                foreach ($result['warnings'] as $warning) {
                    $this->line("    - {$warning}");
                }
                $totalWarnings += count($result['warnings']);
            }

            if ($result['valid']) {
                $validCount++;
            } else {
                $invalidCount++;
            }

            $this->line('');
        }

        $this->line("总结: 有效 {$validCount}, 无效 {$invalidCount}, 错误 {$totalErrors}, 警告 {$totalWarnings}");
    }

    /**
     * 输出JSON格式
     */
    protected function outputJson(array $results): void
    {
        $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * 保存报告
     */
    protected function saveReport(array $results): void
    {
        $report = $this->syntaxValidator->generateReport($results);
        $filename = 'plugin_validation_report_' . date('Y-m-d_H-i-s') . '.txt';
        $path = storage_path("app/{$filename}");

        file_put_contents($path, $report);

        $this->info("验证报告已保存到: {$path}");
    }
}
