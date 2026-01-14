<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearConfigCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-config {group : 配置分组名 (site/content/mail等)} {--key= : 指定清除某个配置项的缓存} {--module= : content分组时指定清除某个模块的缓存}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '根据配置分组清除相关缓存';

    /**
     * 配置分组对应的缓存键模式
     */
    protected array $groupPatterns = [
        'site' => 'config:site:*',                 // 站点信息缓存
        'mail' => 'config:mail:*',                 // 邮件配置缓存
        'system' => 'config:system:*',             // 系统配置缓存
        'auth' => 'config:auth:*',                 // 认证配置缓存
        'storage' => 'config:storage:*',           // 存储配置缓存
        'file' => 'config:file:*',                 // 文件配置缓存
        'all' => 'config:*',                       // 所有配置缓存
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $group = $this->argument('group');
        $key = $this->option('key');
        $module = $this->option('module');

        $supportedGroups = array_merge(array_keys($this->groupPatterns), ['content']);

        if (!in_array($group, $supportedGroups)) {
            $this->error("不支持的配置分组: {$group}");
            $this->info('支持的分组: ' . implode(', ', $supportedGroups));
            return 1;
        }

        if ($key) {
            // 清除指定配置项的缓存
            $cacheKey = "config:{$group}:{$key}";
            $result = Cache::forget($cacheKey);
            if ($result) {
                $this->info("已清除配置项缓存: {$group}:{$key}");
            } else {
                $this->warn("缓存不存在或已被清除: {$group}:{$key}");
            }
        } elseif ($module && $group === 'content') {
            // 清除content分组中指定模块的缓存
            $clearedCount = $this->clearContentModuleCache($module);
            if ($clearedCount > 0) {
                $this->info("已清除content模块缓存: {$module}");
            } else {
                $this->warn("模块缓存不存在或已被清除: {$module}");
            }
        } else {
            // 清除整个分组的缓存
            $clearedCount = $this->clearGroupCache($group);
            $this->info("已清除分组缓存: {$group}");
            $this->comment("清除了 {$clearedCount} 个缓存项");
        }

        $this->comment('操作完成');
        return 0;
    }

    /**
     * 清除指定分组的所有缓存
     */
    protected function clearGroupCache(string $group): int
    {
        // 对于content分组，使用精确清理策略
        if ($group === 'content') {
            return $this->clearContentGroupCaches();
        }

        // 其他分组：清除分组级别的缓存
        $clearedCount = 0;
        $cacheKey = "config:group:{$group}";
        if (Cache::has($cacheKey)) {
            Cache::forget($cacheKey);
            $clearedCount++;
        }

        return $clearedCount;
    }


    /**
     * 清除content分组中指定模块的缓存
     */
    protected function clearContentModuleCache(string $module): int
    {
        $cacheKey = "config:content:{$module}";
        if (Cache::has($cacheKey)) {
            Cache::forget($cacheKey);
            return 1;
        }
        return 0;
    }

    /**
     * 清除所有已知的配置缓存
     */
    protected function clearAllKnownConfigCaches(): int
    {
        $clearedCount = 0;

        // 获取所有支持的分组（排除'all'分组）
        $groups = array_keys($this->groupPatterns);
        $groups = array_filter($groups, fn($group) => $group !== 'all');
        $groups[] = 'content'; // 添加content分组

        foreach ($groups as $group) {
            // 调用统一的分组清除方法
            $clearedCount += $this->clearGroupCache($group);
        }

        return $clearedCount;
    }
}
