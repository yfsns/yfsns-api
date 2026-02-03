<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestCategoryController extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-category-controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test CategoryController methods';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing CategoryController methods...');

        try {
            $categoryService = app(\App\Modules\Category\Services\CategoryService::class);
            $this->info('CategoryService resolved successfully');

            $categories = $categoryService->getCategoryTree();
            $this->info('getCategoryTree() called successfully, returned ' . $categories->count() . ' categories');

            $controller = app(\App\Modules\Category\Controllers\CategoryController::class);
            $this->info('CategoryController resolved successfully');

            // Test if tree method exists
            if (method_exists($controller, 'tree')) {
                $this->info('tree method exists');
            } else {
                $this->error('tree method does not exist');
            }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}
