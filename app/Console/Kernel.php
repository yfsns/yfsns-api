<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\CleanLogsCommand::class,
        Commands\MakeServiceCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // 每天凌晨2点清理30天前的日志
        $schedule->command('logs:clean --days=30')->dailyAt('02:00');

        // 注意：AI审核的定时任务完全由插件自己管理，主程序不设置任何审核相关的定时任务
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        // 加载插件命令
        $this->loadPluginCommands();
    }

    /**
     * 加载插件命令.
     */
    protected function loadPluginCommands(): void
    {
        $pluginPath = base_path('plugins');
        if (! is_dir($pluginPath)) {
            return;
        }

        $plugins = glob($pluginPath . '/*/Console/Commands/*.php');
        foreach ($plugins as $commandFile) {
            $className = $this->getClassNameFromFile($commandFile);
            if ($className && class_exists($className)) {
                $this->commands[] = $className;
            }
        }
    }

    /**
     * 从文件路径获取类名.
     */
    protected function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch) &&
            preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            return $namespaceMatch[1] . '\\' . $classMatch[1];
        }

        return null;
    }
}
