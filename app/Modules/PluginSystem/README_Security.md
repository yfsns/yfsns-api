# 插件系统安全架构

## 概述

YFSNS插件系统实现了多层次的安全防护机制，确保插件作者的语法错误或其他问题不会影响主系统的正常运行。

## 安全层次架构

```
┌─────────────────────────────────────┐
│         插件安全架构层              │
├─────────────────────────────────────┤
│  7. 插件隔离运行时 (Plugin Sandbox) │
│  6. 插件健康监控 (Health Monitor)   │
│  5. 插件安全加载 (Safe Loader)      │
│  4. 插件语法验证 (Syntax Validator) │
│  3. 插件依赖检查 (Dependency Check) │
│  2. 插件兼容性验证 (Compatibility)  │
│  1. 插件结构验证 (Structure Check)  │
└─────────────────────────────────────┘
```

## 1. 插件结构验证

### 功能
- 检查必需文件是否存在 (`Plugin.php`, `composer.json`)
- 验证命名空间和类名格式
- 检查插件目录结构完整性

### 配置
```php
// config/plugin_security.php
'syntax_validation' => [
    'enabled' => true,
    'strict_mode' => true,
],
```

## 2. 插件语法验证

### 功能
- PHP语法检查 (`php -l`)
- 代码质量分析
- 安全漏洞扫描
- 最佳实践检查

### 使用命令
```bash
# 验证所有插件
php artisan plugin:validate

# 验证特定插件
php artisan plugin:validate --plugin=VoteSystem

# 生成验证报告
php artisan plugin:validate --save-report
```

### 验证内容
-  PHP语法错误
-  未使用的变量
-  调试代码 (`var_dump`, `dd()`)
-  安全风险函数
-  代码风格问题

## 3. 插件安全加载器

### 功能
- 语法预检查
- 沙箱环境实例化
- 错误隔离处理
- 加载状态缓存

### 安全特性
```php
$safeLoader = app(\App\Modules\PluginSystem\Services\PluginSafeLoader::class);

// 安全加载插件
$plugin = $safeLoader->loadPlugin('VoteSystem');

// 检查加载状态
$loaded = $safeLoader->getLoadedPlugins();
$failed = $safeLoader->getFailedPlugins();
```

## 4. 插件沙箱环境

### 功能
- 执行时间限制
- 内存使用限制
- 函数调用权限控制
- 错误处理隔离

### 配置限制
```php
// config/plugin_security.php
'sandbox' => [
    'max_execution_time' => 30,      // 30秒
    'max_memory_usage' => 33554432, // 32MB
    'blocked_functions' => [
        'exec', 'shell_exec', 'eval', // 危险函数
    ],
],
```

### 使用沙箱
```php
$sandbox = app(\App\Modules\PluginSystem\Services\PluginSandbox::class);

// 在沙箱中执行插件方法
$result = $sandbox->executePluginMethod($plugin, 'getInfo');

// 创建执行上下文
$context = $sandbox->createExecutionContext($plugin);
$info = $context->getPluginInfo();
```

## 5. 插件健康监控

### 功能
- 定期健康检查
- 性能监控
- 错误统计
- 状态报告生成

### 使用命令
```bash
# 执行健康检查
php artisan plugin:health-check

# 检查特定插件
php artisan plugin:health-check --plugin=VoteSystem

# JSON格式输出
php artisan plugin:health-check --format=json

# 清除缓存
php artisan plugin:health-check --clear-cache
```

### 健康状态
-  **healthy**: 插件运行正常
-  **warning**: 有警告但可运行
-  **unhealthy**: 有错误但不影响系统
- **error**: 严重错误，可能影响系统

## 6. 错误处理机制

### 错误分类
1. **语法错误**: 编译时错误，阻止插件加载
2. **运行时错误**: 执行时错误，沙箱隔离
3. **配置错误**: 配置问题，可降级处理
4. **依赖错误**: 依赖缺失，可提示修复

### 错误恢复策略
```php
// config/plugin_security.php
'error_handling' => [
    'recovery_strategy' => 'isolate', // isolate, disable, ignore
    'separate_log_file' => true,
    'send_notifications' => false,
],
```

## 7. 插件隔离机制

### 隔离级别
- **namespace**: 命名空间隔离（推荐）
- **thread**: 线程隔离（高级）
- **process**: 进程隔离（最高安全）

### 共享资源控制
```php
'isolation' => [
    'level' => 'namespace',
    'shared_resources' => [
        'database' => true,   // 共享数据库
        'cache' => true,      // 共享缓存
        'filesystem' => false, // 不共享文件系统
    ],
],
```

## 安全最佳实践

### 插件开发规范

#### 1. 错误处理
```php
//  推荐：使用try-catch
try {
    $result = $this->riskyOperation();
} catch (\Throwable $e) {
    Log::error('Plugin operation failed', [
        'plugin' => $this->getInfo()['name'],
        'error' => $e->getMessage()
    ]);
    return false;
}

//  避免：裸奔代码
$result = $this->riskyOperation(); // 可能导致Fatal Error
```

#### 2. 资源限制
```php
//  推荐：限制内存使用
$images = Image::where('status', 'pending')->limit(100)->get();

//  避免：无限制查询
$images = Image::all(); // 可能导致内存溢出
```

#### 3. 安全函数调用
```php
//  推荐：使用Laravel辅助函数
Storage::disk('public')->put($path, $content);

//  避免：直接文件操作
file_put_contents(public_path($path), $content); // 不安全
```

### 系统管理员指南

#### 定期维护
```bash
# 每日健康检查
0 2 * * * php artisan plugin:health-check

# 每周语法验证
0 3 * * 1 php artisan plugin:validate --save-report

# 监控错误日志
tail -f storage/logs/plugin_errors.log
```

#### 紧急处理
```bash
# 禁用问题插件
php artisan plugin:disable ProblemPlugin

# 清理缓存
php artisan plugin:health-check --clear-cache

# 查看详细状态
php artisan plugin:health-check --format=json
```

## 故障排除

### 常见问题

#### 插件无法加载
```bash
# 检查语法
php artisan plugin:validate --plugin=PluginName

# 查看健康状态
php artisan plugin:health-check --plugin=PluginName

# 检查日志
tail -f storage/logs/laravel.log
```

#### 性能问题
```bash
# 检查执行时间
php artisan plugin:health-check

# 查看内存使用
php artisan tinker
>>> memory_get_peak_usage(true) / 1024 / 1024 . ' MB'
```

#### 安全警报
```bash
# 扫描安全问题
php artisan plugin:validate

# 查看详细报告
cat storage/app/plugin_validation_report_*.txt
```

## 配置参考

### 环境变量
```bash
# 安全设置
PLUGIN_SANDBOX_ENABLED=true
PLUGIN_SYNTAX_VALIDATION_ENABLED=true
PLUGIN_SAFE_LOADING=true

# 性能限制
PLUGIN_MAX_EXECUTION_TIME=30
PLUGIN_MAX_MEMORY_USAGE=33554432

# 监控设置
PLUGIN_HEALTH_MONITOR_ENABLED=true
PLUGIN_HEALTH_CACHE_TTL=3600

# 开发设置
PLUGIN_DEBUG_MODE=false
PLUGIN_VERBOSE_ERRORS=false
```

### 配置文件
```php
// config/plugin_security.php
return [
    'syntax_validation' => ['enabled' => true],
    'sandbox' => [
        'max_execution_time' => 30,
        'blocked_functions' => ['exec', 'eval'],
    ],
    'health_monitor' => ['enabled' => true],
];
```

## 总结

通过多层次的安全架构，我们确保了：

1. **插件错误隔离**: 单个插件错误不会影响整个系统
2. **语法预检查**: 在加载前发现并阻止有问题的插件
3. **运行时保护**: 沙箱环境限制插件的执行权限
4. **健康监控**: 实时监控插件状态，及时发现问题
5. **错误恢复**: 多种策略确保系统稳定性

这种设计既保证了插件系统的灵活性和扩展性，又维护了主系统的安全性和稳定性。
