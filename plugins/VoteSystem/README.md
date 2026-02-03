# 投票系统插件 (VoteSystem Plugin)

## 概述

这是一个完整的投票系统插件，为YFSNS提供全面的投票功能支持。该插件采用了模块化设计，支持多种投票类型、权限控制和统计功能。

## 架构设计

### 1. 插件类型分类

根据插件的功能和集成方式，我们将插件分为以下几种类型：

#### 1.1 通道插件 (Channel Plugins)
- **特点**: 提供标准化的接口，如短信发送、文件存储
- **示例**: SMS模块、File模块
- **架构**: 统一的接口契约，支持多供应商切换

#### 1.2 功能插件 (Functional Plugins)
- **特点**: 提供完整的功能模块，如投票、商城、论坛
- **示例**: VoteSystem、ShopSystem、ForumSystem
- **架构**: 自主的MVC结构，完整的生命周期管理

#### 1.3 扩展插件 (Extension Plugins)
- **特点**: 扩展现有功能，如评论、分享、统计
- **示例**: CommentPlus、ShareTools、Analytics
- **架构**: 钩子系统，事件驱动

#### 1.4 工具插件 (Utility Plugins)
- **特点**: 提供工具类功能，如备份、监控、缓存
- **示例**: BackupTool、SystemMonitor、CacheManager
- **架构**: 服务提供者模式

### 2. 功能插件架构

#### 2.1 目录结构
```
plugins/VoteSystem/
├── Plugin.php                 # 插件主类
├── composer.json             # Composer配置
├── config/                   # 配置文件
├── database/                 # 数据库相关
│   ├── migrations/          # 迁移文件
│   └── seeders/             # 数据填充
├── Http/                     # HTTP层
│   ├── Controllers/         # 控制器
│   ├── Requests/            # 请求验证
│   └── Middleware/          # 中间件
├── Models/                   # 数据模型
├── Services/                 # 业务逻辑
├── Policies/                 # 权限策略
├── routes/                   # 路由定义
├── resources/                # 资源文件
│   ├── views/               # 视图模板
│   └── lang/                # 语言文件
└── Providers/                # 服务提供者
```

#### 2.2 核心组件

##### Plugin.php (插件主类)
```php
class Plugin extends BasePlugin
{
    public function getInfo(): array          // 插件信息
    public function enable(): void           // 启用插件
    public function disable(): void          // 禁用插件
    public function install(): bool          // 安装插件
    public function uninstall(): bool        // 卸载插件
    public function getMenuItems(): array    // 菜单项
    public function getNavigationItems(): array // 导航项
}
```

##### ServiceProvider (服务提供者)
- 注册服务绑定
- 加载配置、路由、视图
- 发布资源文件
- 插件生命周期管理

##### Models (数据模型)
- Vote: 投票主题
- VoteOption: 投票选项
- VoteRecord: 投票记录

##### Services (业务服务)
- VoteService: 投票业务逻辑
- 处理投票创建、投票、统计等核心功能

##### Controllers (控制器)
- 前端API控制器: 处理用户投票请求
- 管理后台控制器: 处理管理员管理请求

#### 2.3 数据库设计

##### plug_vote_votes (投票表)
- 投票基本信息
- 配置选项 (JSON)
- 统计信息

##### plug_vote_options (选项表)
- 选项内容
- 投票计数
- 排序和状态

##### plug_vote_records (记录表)
- 投票记录
- 用户/IP追踪
- 审计信息

#### 2.4 API设计

##### 前端API
```
GET    /api/v1/plugins/vote-system/votes           # 投票列表
GET    /api/v1/plugins/vote-system/votes/{id}      # 投票详情
POST   /api/v1/plugins/vote-system/votes/{id}/vote # 参与投票
GET    /api/v1/plugins/vote-system/votes/{id}/results # 投票结果
```

##### 管理API
```
GET    /api/admin/plugins/vote-system/votes        # 投票管理列表
POST   /api/admin/plugins/vote-system/votes        # 创建投票
PUT    /api/admin/plugins/vote-system/votes/{id}   # 更新投票
DELETE /api/admin/plugins/vote-system/votes/{id}   # 删除投票
POST   /api/admin/plugins/vote-system/votes/{id}/activate # 激活投票
```

#### 2.5 权限系统

##### 插件权限
- `vote.create`: 创建投票
- `vote.edit`: 编辑投票
- `vote.delete`: 删除投票
- `vote.view`: 查看投票
- `vote.vote`: 参与投票
- `vote.manage`: 管理投票

##### 权限策略
- 基于用户的权限控制
- 支持角色和权限组
- 细粒度的访问控制

#### 2.6 配置管理

##### 插件配置
```php
[
    'keep_data_on_uninstall' => false,
    'defaults' => [
        'allow_guest' => false,
        'show_results' => true,
        'require_login' => true,
        'max_votes' => 1,
    ],
    'limits' => [
        'max_options_per_vote' => 20,
        'max_votes_per_user' => 5,
    ],
]
```

##### 配置发布
```bash
php artisan vendor:publish --provider="Plugins\VoteSystem\Providers\VoteSystemServiceProvider" --tag=vote-system-config
```

## 安装和使用

### 1. 安装插件
```bash
php artisan plugin:install VoteSystem
```

### 2. 启用插件
```bash
php artisan plugin:enable VoteSystem
```

### 3. 运行迁移
```bash
php artisan migrate
```

### 4. 配置插件
```php
// config/plugins/vote_system.php
return [
    // 配置选项
];
```

## 开发指南

### 创建新的功能插件

1. **创建目录结构**
```bash
mkdir -p plugins/YourPlugin/{Http/Controllers,Models,Services,routes,database/migrations,config}
```

2. **实现Plugin类**
```php
<?php
namespace Plugins\YourPlugin;

use App\Modules\PluginSystem\BasePlugin;

class Plugin extends BasePlugin
{
    public function getInfo(): array
    {
        return [
            'name' => 'your_plugin',
            'version' => '1.0.0',
            'description' => 'Your plugin description',
        ];
    }
}
```

3. **创建ServiceProvider**
```php
<?php
namespace Plugins\YourPlugin\Providers;

use Illuminate\Support\ServiceProvider;

class YourPluginServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
```

4. **定义路由和控制器**
```php
// routes/api.php
Route::get('your-endpoint', [YourController::class, 'index']);
```

### 插件生命周期

1. **安装**: 创建数据库表、初始化数据
2. **启用**: 注册路由、权限、菜单
3. **运行**: 处理用户请求
4. **禁用**: 清理路由、权限、菜单
5. **卸载**: 删除数据库表、清理数据

### 最佳实践

1. **命名规范**: 使用插件名前缀，如 `plug_yourplugin_`
2. **错误处理**: 统一错误响应格式
3. **数据验证**: 使用Form Request验证
4. **权限控制**: 在控制器和策略中检查权限
5. **缓存策略**: 合理使用缓存提升性能
6. **日志记录**: 记录重要操作和错误
7. **文档编写**: 完整的API文档和使用说明

## 扩展开发

### 钩子系统
```php
// 插件中触发钩子
$this->fireHook('vote.created', $vote);

// 其他插件监听钩子
\Event::listen('vote.created', function($vote) {
    // 处理逻辑
});
```

### 事件系统
```php
// 触发事件
event(new VoteCreated($vote));

// 监听事件
\EventServiceProvider::listen(VoteCreated::class, VoteCreatedListener::class);
```

### API扩展
```php
// 注册额外的API路由
$this->app['router']->group(['prefix' => 'vote-system'], function($router) {
    // 自定义路由
});
```

## 总结

功能插件架构提供了：
- **完整的MVC结构**: 独立的控制器、模型、服务
- **自主的数据库设计**: 插件拥有自己的数据表
- **灵活的API设计**: 自定义路由和接口
- **完善的权限控制**: 细粒度的权限管理
- **标准化的生命周期**: 安装、启用、禁用、卸载
- **可扩展的配置**: 灵活的配置管理

这种架构适用于开发完整的业务功能模块，为YFSNS提供了强大的插件扩展能力。
