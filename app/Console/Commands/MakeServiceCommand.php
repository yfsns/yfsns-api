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

use function dirname;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建一个新的Service类';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $name = $this->argument('name');
        $path = $this->getPath($name);

        if (File::exists($path)) {
            $this->error("Service {$name} 已经存在!");

            return;
        }

        $this->createDirectory($name);
        $this->createService($name);

        $this->info("Service {$name} 创建成功!");
    }

    protected function getPath($name)
    {
        $name = str_replace('\\', '/', $name);

        return app_path('Services/' . $name . '.php');
    }

    protected function createDirectory($name): void
    {
        $name = str_replace('\\', '/', $name);
        $directory = dirname(app_path('Services/' . $name . '.php'));

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }

    protected function createService($name): void
    {
        $name = str_replace('\\', '/', $name);
        $path = app_path('Services/' . $name . '.php');

        $namespace = 'App\\Services\\' . dirname(str_replace('/', '\\', $name));
        $className = basename($name);

        $stub = $this->getStub();
        $stub = str_replace('{{namespace}}', $namespace, $stub);
        $stub = str_replace('{{class}}', $className, $stub);

        File::put($path, $stub);
    }

    protected function getStub()
    {
        return <<<'EOT'
<?php

namespace {{namespace}};

class {{class}}
{
    public function __construct()
    {
    }
}
EOT;
    }
}
