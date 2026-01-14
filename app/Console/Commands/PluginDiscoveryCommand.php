<?php

namespace App\Console\Commands;

use App\Modules\PluginSystem\Services\PluginDiscoveryService;
use Illuminate\Console\Command;

/**
 * 插件发现命令
 *
 * 手动发现和注册插件到数据库
 */
class PluginDiscoveryCommand extends Command
{
    /**
     * 命令签名
     */
    protected $signature = 'plugin:discover
                            {--force : 强制重新发现，清除旧记录}
                            {--single= : 仅发现指定插件}';

    /**
     * 命令描述
     */
    protected $description = '手动发现和注册插件到数据库';

    /**
     * 插件发现服务
     */
    protected PluginDiscoveryService $discoveryService;

    /**
     * 构造函数
     */
    public function __construct(PluginDiscoveryService $discoveryService)
    {
        parent::__construct();
        $this->discoveryService = $discoveryService;
    }

    /**
     * 执行命令
     */
    public function handle(): int
    {
        $force = $this->option('force');
        $singlePlugin = $this->option('single');

        $this->info('🔍 开始插件发现...');

        // 检查是否指定了单个插件
        if ($singlePlugin) {
            return $this->discoverSinglePlugin($singlePlugin);
        }

        // 检查是否需要强制重新发现
        if ($force) {
            $this->warn('⚠️  将清除旧的发现记录...');
            if (!$this->confirm('确定要继续吗？这将删除所有未安装的插件记录')) {
                $this->info('操作已取消');
                return Command::SUCCESS;
            }

            $cleared = $this->discoveryService->clearDiscoveryRecords();
            if (!$cleared) {
                $this->error('❌ 清除发现记录失败');
                return Command::FAILURE;
            }

            $this->info('✅ 发现记录已清除');
        }

        // 执行插件发现
        $result = $this->discoveryService->discoverPlugins();

        if (!$result['success']) {
            $this->error('❌ 插件发现失败: ' . $result['message']);
            return Command::FAILURE;
        }

        // 显示结果
        $this->info('✅ 插件发现完成！');
        $this->newLine();

        // 显示统计信息
        $this->table(
            ['指标', '数量'],
            [
                ['发现的插件', $result['discovered']],
                ['成功注册', $result['registered']],
                ['注册失败', $result['failed']],
            ]
        );

        // 显示详细的插件信息
        if (!empty($result['plugins'])) {
            $this->newLine();
            $this->info('📦 发现的插件详情：');

            $tableData = [];
            foreach ($result['plugins'] as $plugin) {
                $tableData[] = [
                    $plugin['name'],
                    $plugin['info']['version'] ?? 'N/A',
                    $plugin['info']['description'] ?? 'N/A',
                    '已发现'
                ];
            }

            $this->table(
                ['插件名称', '版本', '描述', '状态'],
                $tableData
            );
        }

        // 显示注册结果
        if (!empty($result['registration']['results'])) {
            $this->newLine();
            $this->info('📝 注册结果详情：');

            foreach ($result['registration']['results'] as $pluginName => $registrationResult) {
                $status = $registrationResult['success'] ? '✅ 成功' : '❌ 失败';
                $message = $registrationResult['message'];

                $this->line("{$pluginName}: {$status} - {$message}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * 发现单个插件
     */
    protected function discoverSinglePlugin(string $pluginName): int
    {
        $this->info("🔍 发现单个插件: {$pluginName}");

        $result = $this->discoveryService->discoverPlugin($pluginName);

        if (!$result['success']) {
            $this->error('❌ 插件发现失败: ' . $result['message']);
            return Command::FAILURE;
        }

        $this->info('✅ 插件发现成功！');

        // 显示插件信息
        if (isset($result['plugin'])) {
            $plugin = $result['plugin'];
            $this->newLine();
            $this->table(
                ['属性', '值'],
                [
                    ['插件名称', $plugin['name']],
                    ['类名', $plugin['class']],
                    ['版本', $plugin['info']['version'] ?? 'N/A'],
                    ['描述', $plugin['info']['description'] ?? 'N/A'],
                    ['作者', $plugin['info']['author'] ?? 'N/A'],
                ]
            );
        }

        // 显示注册结果
        if (isset($result['registration'])) {
            $registration = $result['registration'];
            $status = $registration['success'] ? '✅ 成功' : '❌ 失败';
            $this->line("注册结果: {$status} - {$registration['message']}");
        }

        return Command::SUCCESS;
    }
}
