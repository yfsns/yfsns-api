<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearContentReviewCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-content-review-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清除所有内容审核缓存，确保配置变更立即生效';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('开始清除内容审核缓存...');

        $service = app(\App\Modules\System\Services\ContentReviewConfigService::class);
        $service->clearAllCache();

        $this->info('内容审核缓存清除完成');
    }
}
