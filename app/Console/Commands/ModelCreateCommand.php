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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

use function sprintf;

class ModelCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmodel {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $table_name = $this->argument('table');

        if (! Schema::hasTable($table_name)) {
            $this->error("The table '{$table_name}' does not exist.");

            return 1; // 返回非零状态码表示出错
        }

        $table_name_data = explode('_', $table_name);
        foreach ($table_name_data as &$item) {
            $item = ucfirst($item);
        }
        $model_name = implode('', $table_name_data);

        // 检查字段是否存在
        if (Schema::hasColumn($table_name, 'deleted_at')) {
            $stub_path = app_path('Console/Commands/stubs/model.create.withsoftdelete.stub');
        } else {
            $stub_path = app_path('Console/Commands/stubs/model.create.stub');
        }
        $stub_path_contents = file_get_contents($stub_path);
        $stub_path_contents = sprintf($stub_path_contents, $model_name, $table_name);

        $model_path = app_path("Models/$model_name.php");
        file_put_contents($model_path, $stub_path_contents);
        $this->info('create model success');

        $cmd = "php artisan ide-helper:models App\\Models\\$model_name -W";

        exec($cmd, $output, $returnVar);

        if ($returnVar === 0) {
            // 命令成功执行
            $this->info('ide model success');
        } else {
            // 命令执行失败
            $this->error("ide model fail $output");
        }
    }
}
