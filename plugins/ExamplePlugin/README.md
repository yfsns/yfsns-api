# 示例插件 (Example Plugin)

这是一个演示如何正确开发安全合规插件的示例插件。

## 插件开发规范

### 1. 插件结构要求

插件必须包含以下文件：
- `Plugin.php` - 插件主文件，实现 `PluginInterface`
- `composer.json` - 插件配置和依赖声明

可选文件：
- `routes/` - 路由文件目录
- `Http/Controllers/` - 控制器目录
- `Database/Migrations/` - 数据库迁移文件
- `config/` - 配置文件

### 2. 安全验证清单

插件启用前会进行以下验证：

#### ✅ 结构验证
- [x] 必需文件存在性
- [x] 目录权限检查

#### ✅ 信息验证
- [x] 插件名称格式（字母开头，只能包含字母、数字、下划线）
- [x] 版本号格式（x.y.z）
- [x] 必需字段完整性

#### ✅ 代码质量验证
- [x] PHP语法正确性
- [x] 危险函数检测（`exec`, `eval`, `shell_exec`等）
- [x] 调试代码检查（`var_dump`, `dd()`等）

#### ✅ 安全验证
- [x] 路由安全性检查
- [x] 权限定义格式验证
- [x] 中间件使用规范检查

#### ✅ 依赖验证
- [x] Laravel版本兼容性
- [x] PHP版本要求检查

### 3. 路由注册安全

#### 安全的路由写法：
```php
// ✅ 推荐：明确指定控制器和方法
Route::get('/examples', [ExampleController::class, 'index']);

// ❌ 避免：使用闭包路由（难以验证安全性）
Route::get('/examples', function () {
    return 'Hello World';
});

// ✅ 推荐：使用中间件保护敏感路由
Route::middleware(['auth:api', 'can:example.create'])->group(function () {
    Route::post('/examples', [ExampleController::class, 'store']);
});
```

#### 路由命名规范：
```php
// 插件路由应使用插件名前缀
Route::get('/examples', [ExampleController::class, 'index'])
    ->name('example.index');
```

### 4. 权限定义规范

```php
protected function initialize(): void
{
    // 权限定义格式：[permission_key => permission_description]
    $this->permissions = [
        'example.view' => '查看示例内容',
        'example.create' => '创建示例内容',
        'example.edit' => '编辑示例内容',
        'example.delete' => '删除示例内容',
        'example.admin' => '管理示例插件',
    ];
}
```

### 5. 依赖声明

在 `composer.json` 中正确声明依赖：

```json
{
    "require": {
        "php": ">=8.1",
        "laravel/framework": ">=10.0"
    }
}
```

### 6. 控制器开发规范

```php
<?php

namespace Plugins\ExamplePlugin\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class ExampleController extends ApiController
{
    /**
     * 获取示例列表
     */
    public function index(Request $request)
    {
        // 实现逻辑
        return $this->success($data, '获取成功');
    }

    /**
     * 创建示例
     */
    public function store(Request $request)
    {
        // 验证权限已在路由层面完成
        // 业务逻辑验证
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // 实现逻辑
        return $this->success($data, '创建成功');
    }
}
```

### 7. 数据库迁移规范

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExamplePluginTables extends Migration
{
    public function up()
    {
        // 使用插件特定的表名前缀
        Schema::create('plug_example_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('plug_example_items');
    }
}
```

## 验证结果示例

当插件通过所有验证后，API会返回：

```json
{
    "code": 200,
    "message": "插件启用成功",
    "data": {
        "plugin": {
            "name": "example_plugin",
            "display_name": "示例插件",
            "version": "1.0.0",
            "permissions": {
                "example.view": "查看示例内容",
                "example.create": "创建示例内容"
            }
        },
        "validation": {
            "valid": true,
            "checks": {
                "structure": {"valid": true},
                "info": {"valid": true},
                "code": {"valid": true, "warnings": []},
                "security": {"valid": true},
                "dependencies": {"valid": true}
            }
        }
    }
}
```

## 故障排除

### 常见验证失败原因：

1. **缺少必需文件**
   - 确保 `Plugin.php` 和 `composer.json` 存在

2. **插件类未实现接口**
   - 确保插件类继承 `BasePlugin` 或实现 `PluginInterface`

3. **权限定义格式错误**
   - 确保权限是 `['key' => 'description']` 格式

4. **路由安全问题**
   - 避免使用闭包路由
   - 确保敏感路由有适当的中间件保护

5. **依赖版本不兼容**
   - 检查 `composer.json` 中的版本要求

## 最佳实践

1. **始终进行输入验证**
2. **使用参数绑定而不是路由参数**
3. **记录重要操作日志**
4. **优雅处理异常**
5. **遵循Laravel开发规范**

这个示例插件展示了如何开发一个完全符合安全要求的插件。
