<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreatePlugin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:create {name : 插件名称（驼峰格式）} {--description= : 插件描述} {--author= : 开发者名称}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建符合标准的插件模板';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $description = $this->option('description') ?: '插件描述';
        $author = $this->option('author') ?: '开发者';

        // 验证插件名称格式
        if (!preg_match('/^[A-Z][a-zA-Z]*$/', $name)) {
            $this->error('插件名称必须是驼峰格式（首字母大写）');
            return 1;
        }

        $pluginPath = base_path("plugins/{$name}");

        // 检查插件是否已存在
        if (File::exists($pluginPath)) {
            $this->error("插件 {$name} 已存在！");
            return 1;
        }

        $this->info("正在创建插件: {$name}");
        $this->createPluginStructure($pluginPath, $name, $description, $author);
        $this->createPluginFiles($pluginPath, $name, $description, $author);

        $this->info(" 插件 {$name} 创建成功！");
        $this->info("📁 插件路径: {$pluginPath}");
        $this->info("📋 下一步:");
        $this->info("  1. 编辑 Plugin.php 文件，添加具体功能");
        $this->info("  2. 修改数据库迁移文件，添加业务字段");
        $this->info("  3. 运行 php artisan plugin:install {$name} 安装插件");

        return 0;
    }

    /**
     * 创建插件目录结构
     */
    private function createPluginStructure(string $pluginPath, string $name, string $description, string $author): void
    {
        $directories = [
            'database/migrations',
            'config',
            'resources',
            'routes',
            'src',
            'tests',
        ];

        foreach ($directories as $dir) {
            $path = $pluginPath . '/' . $dir;
            File::makeDirectory($path, 0755, true);
            $this->info("📁 创建目录: {$dir}");
        }
    }

    /**
     * 创建插件文件
     */
    private function createPluginFiles(string $pluginPath, string $name, string $description, string $author): void
    {
        // 创建Plugin.php
        $this->createPluginClass($pluginPath, $name, $description, $author);

        // 创建设置表迁移
        $this->createSettingsMigration($pluginPath, $name);

        // 创建composer.json
        $this->createComposerJson($pluginPath, $name, $description, $author);

        // 创建README.md
        $this->createReadme($pluginPath, $name, $description, $author);

        // 创建config文件
        $this->createConfigFile($pluginPath, $name);
    }

    /**
     * 创建插件主类
     */
    private function createPluginClass(string $pluginPath, string $name, string $description, string $author): void
    {
        $content = "<?php

namespace Plugins\\{$name};

use App\\Modules\\Plugin\\Support\\StandardPlugin;

class Plugin extends StandardPlugin
{
    protected string \$name = '{$name}';
    protected string \$version = '1.0.0';
    protected string \$description = '{$description}';
    protected string \$author = '{$author}';

    /**
     * 执行自定义安装逻辑
     */
    protected function performInstall(): array
    {
        try {
            // 设置默认配置
            \$this->setSetting('enabled', true, 'bool', 'general', '插件启用状态');
            \$this->setSetting('debug_mode', false, 'bool', 'general', '调试模式');

            // TODO: 添加插件特定的安装逻辑
            // 例如：创建目录、初始化数据等

            return [
                'success' => true,
                'message' => '{$name}插件安装成功',
            ];
        } catch (\\Exception \$e) {
            return [
                'success' => false,
                'message' => '{$name}插件安装失败: ' . \$e->getMessage(),
            ];
        }
    }

    /**
     * 执行自定义卸载逻辑
     */
    protected function performUninstall(): array
    {
        try {
            // TODO: 添加插件特定的卸载逻辑
            // 例如：清理数据、删除文件等

            return [
                'success' => true,
                'message' => '{$name}插件卸载成功',
            ];
        } catch (\\Exception \$e) {
            return [
                'success' => false,
                'message' => '{$name}插件卸载失败: ' . \$e->getMessage(),
            ];
        }
    }

    /**
     * 重写依赖检查（如果有特殊要求）
     */
    public function getDependencies(): array
    {
        return [
            'php' => ['>=8.1'],
            'extensions' => ['pdo', 'json'],
            'plugins' => [], // 依赖的其他插件
        ];
    }
}
";
        File::put($pluginPath . '/Plugin.php', $content);
        $this->info("📄 创建文件: Plugin.php");
    }

    /**
     * 创建设置表迁移
     */
    private function createSettingsMigration(string $pluginPath, string $name): void
    {
        $tableName = 'plug_' . Str::snake($name) . '_settings';
        $className = 'Create' . Str::studly($tableName) . 'Table';
        $fileName = date('Y_m_d_His') . '_create_' . Str::snake($tableName) . '_table.php';

        $content = "<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('key')->unique()->comment('配置键');
            \$table->text('value')->nullable()->comment('配置值');
            \$table->string('type')->default('string')->comment('配置类型：string, int, bool, json');
            \$table->string('group')->default('general')->comment('配置分组');
            \$table->text('description')->nullable()->comment('配置描述');
            \$table->boolean('is_public')->default(false)->comment('是否为公开配置');
            \$table->timestamps();

            \$table->index(['group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
";
        File::put($pluginPath . '/database/migrations/' . $fileName, $content);
        $this->info("📄 创建文件: database/migrations/{$fileName}");
    }

    /**
     * 创建composer.json
     */
    private function createComposerJson(string $pluginPath, string $name, string $description, string $author): void
    {
        $content = '{
    "name": "yfsns/' . Str::kebab($name) . '-plugin",
    "description": "' . $description . '",
    "type": "yfsns-plugin",
    "version": "1.0.0",
    "authors": [
        {
            "name": "' . $author . '",
            "email": "author@example.com"
        }
    ],
    "require": {
        "php": ">=8.1"
    },
    "autoload": {
        "psr-4": {
            "Plugins\\\\' . $name . '\\\\": "src/"
        }
    },
    "extra": {
        "yfsns-plugin": {
            "name": "' . $name . '",
            "version": "1.0.0"
        }
    }
}
';
        File::put($pluginPath . '/composer.json', $content);
        $this->info("📄 创建文件: composer.json");
    }

    /**
     * 创建README.md
     */
    private function createReadme(string $pluginPath, string $name, string $description, string $author): void
    {
        $content = "# {$name} 插件

{$description}

## 安装要求

- PHP >= 8.1
- Laravel >= 10.0
- YFSNS >= 1.0

## 功能特性

- TODO: 添加功能特性描述

## 配置说明

插件安装后会自动创建配置表，可以通过以下方式配置：

```php
// 在插件代码中读取配置
\$value = \$this->getSetting('config_key', 'default_value');

// 在插件代码中设置配置
\$this->setSetting('config_key', 'value', 'string', 'general', '配置描述');
```

## 开发说明

### 目录结构

```
{$name}/
├── Plugin.php           # 插件主文件
├── composer.json        # Composer配置
├── README.md           # 说明文档
├── database/
│   └── migrations/     # 数据库迁移
├── config/             # 配置文件
├── resources/          # 资源文件
├── routes/             # 路由定义
├── src/                # 源代码
└── tests/              # 测试文件
```

### 自定义开发

1. 编辑 `Plugin.php` 文件，添加具体功能
2. 在 `src/` 目录下添加业务逻辑类
3. 在 `routes/` 目录下定义API路由
4. 在 `tests/` 目录下编写测试

## 作者

{$author}

## 许可证

Apache License 2.0
";
        File::put($pluginPath . '/README.md', $content);
        $this->info("📄 创建文件: README.md");
    }

    /**
     * 创建配置文件
     */
    private function createConfigFile(string $pluginPath, string $name): void
    {
        $fileName = Str::snake($name) . '.php';
        $content = "<?php

return [
    /*
    |--------------------------------------------------------------------------
    | {$name} 插件配置
    |--------------------------------------------------------------------------
    |
    | {$name} 插件的配置文件
    |
    */

    'enabled' => env('PLUGIN_{$name}_ENABLED', true),

    'debug' => env('PLUGIN_{$name}_DEBUG', false),

    // TODO: 添加更多配置项
];
";
        File::put($pluginPath . '/config/' . $fileName, $content);
        $this->info("📄 创建文件: config/{$fileName}");
    }
}
