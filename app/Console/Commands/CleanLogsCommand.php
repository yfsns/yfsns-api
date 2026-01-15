<?php

/**
 * YFSNS社交网络服务系统
 *
 * Copyright (C) 2025 合肥音符信息科技有限公司
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Console\Commands;

use function count;

use Exception;
use Illuminate\Console\Command;

class CleanLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clean {--days=30 : 保留最近N天的日志}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清理指定天数之前的日志文件';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {
            $days = (int) $this->option('days');
            $path = storage_path('logs');
            $cutoffDate = now()->subDays($days);
            $deletedCount = 0;
            $totalSize = 0;

            // 获取所有日志文件
            $files = glob($path . '/*.log*');

            foreach ($files as $file) {
                if (! is_file($file)) {
                    continue;
                }

                $fileTime = filemtime($file);
                $fileDate = \Carbon\Carbon::createFromTimestamp($fileTime);
                $fileSize = filesize($file);

                // 如果文件修改时间早于截止日期，则删除
                if ($fileDate->lt($cutoffDate)) {
                    $totalSize += $fileSize;
                    unlink($file);
                    $deletedCount++;
                    $this->line('已删除: ' . basename($file) . ' (' . $this->formatBytes($fileSize) . ', ' . $fileDate->format('Y-m-d') . ')');
                }
            }

            if ($deletedCount > 0) {
                $this->info("清理完成：删除了 {$deletedCount} 个日志文件，释放了 " . $this->formatBytes($totalSize) . ' 空间');
            } else {
                $this->info("没有需要清理的日志文件（保留最近 {$days} 天的日志）");
            }
        } catch (Exception $e) {
            $this->error('日志文件清理失败：' . $e->getMessage());
        }
    }

    /**
     * 格式化字节数.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
