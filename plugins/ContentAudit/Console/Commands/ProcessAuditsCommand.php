<?php

namespace Plugins\ContentAudit\Console\Commands;

use Illuminate\Console\Command;
use Plugins\ContentAudit\Jobs\ProcessPendingAuditsJob;

/**
 * 处理待审核任务命令.
 *
 * 手动触发指定内容的审核任务（用于测试或特殊情况）
 */
class ProcessAuditsCommand extends Command
{
    protected $signature = 'contentaudit:process {contentType} {contentId}';

    protected $description = '手动处理指定内容的待审核任务';

    public function handle(): void
    {
        $contentType = $this->argument('contentType');
        $contentId = (int) $this->argument('contentId');

        $this->info("开始处理待审核任务: {$contentType} #{$contentId}");

        ProcessPendingAuditsJob::dispatch($contentType, $contentId);

        $this->info('待审核任务已加入队列');
    }
}
