<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestTagController extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-tag-controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test TagController methods';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing TagController methods...');

        try {
            $tagService = app(\App\Modules\Tag\Services\TagService::class);
            $this->info('TagService resolved successfully');

            $tags = $tagService->getPopularTags(5);
            $this->info('getPopularTags() called successfully, returned ' . $tags->count() . ' tags');

            $controller = app(\App\Modules\Tag\Controllers\TagController::class);
            $this->info('TagController resolved successfully');

            // Test if popular method exists
            if (method_exists($controller, 'popular')) {
                $this->info('popular method exists');
            } else {
                $this->error('popular method does not exist');
            }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}
