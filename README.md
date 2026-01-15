

# YFSNS 社交网络服务系统（后端）

YFSNS-API 是一个基于 **Laravel 12 + PHP 8.4** 构建的高性能社交网络服务后端。系统采用模块化架构设计，提供用户认证、内容创作、社交互动、财务管理等完整的社交能力，面向前后端分离的 API 场景。

## ✨ 主要特性

*   **统一认证体系**：支持用户名、邮箱、手机号登录，集成了基于 Sanctum 的 API Token 认证。
*   **完善的内容生态**：支持动态（帖子）、文章、评论（含楼中楼回复）、话题（Topic）功能。
*   **灵活的社交互动**：点赞、收藏、分享、@提及、关注/粉丝系统。
*   **可扩展的审核模块**：内置内容审核机制，支持 AI 审核与人工审核切换，采用 Trait 设计易于扩展。
*   **插件系统**：支持插件化开发，包含插件安装、卸载、启用/禁用、语法验证、安全检查及健康监控功能。
*   **财务与钱包**：完整的积分、虚拟币（音符币）、余额、优惠券系统。
*   **基础设施**：敏感词过滤、IP 定位服务、全文搜索服务、文件存储服务。

## 🛠 技术栈

*   **核心框架**: Laravel 12.44
*   **编程语言**: PHP ≥ 8.4
*   **数据库**: MySQL ≥ 8.0
*   **缓存**: Redis ≥ 6.0
*   **消息队列**: Redis Queue
*   **包管理**: Composer

## 📦 核心模块概览

### 1. 用户与权限 (User & Auth)
提供完整的用户生命周期管理。
*   **User**: 用户模型、资料管理、资产（头像、背景图）。
*   **Auth**: 登录、注册、验证码（邮件/短信）、Token 管理。
*   **UserRole**: 基于角色的权限控制 (RBAC)，包含角色创建、权限分配。
*   **UserFollow**: 用户关注与粉丝关系逻辑。
*   **UserMention**: @提及功能，记录谁在什么时候提及了用户。

### 2. 内容与社交 (Content & Social)
系统的核心互动区域。
*   **Post**: 动态发布，支持多种可见性（公开、仅粉丝、好友圈、私密），包含转发（Repost）机制。
*   **Comment**: 评论系统，支持无限层级回复，包含热度评分（Hot Score）计算。
*   **Topic**: 话题管理，支持话题搜索、趋势统计、热门推荐。
*   **Collect/Share/Like**: 分别对应收藏、分享、点赞的标准化MorphTo关联模型。

### 3. 地理位置与搜索 (Location & Search)
*   **Location**: 基于驱动的位置服务，支持腾讯地图等提供商，包含逆地理编码、IP 定位。
*   **Search**: 全文搜索模块，支持全局搜索及分类搜索（用户、动态、评论、话题），包含搜索建议与热词管理。

### 4. 审核与安全 (Review & Security)
*   **Review**: 通用审核模块，支持内容（Post/Comment）的人工审核与 AI 审核。
    *   提供 `HasReviewable` Trait，可快速为任意模型添加审核功能。
    *   记录审核日志（ReviewLog）。
*   **SensitiveWord**: 敏感词管理，支持多种处理动作（替换、拒绝、审核）以及分类（政治、色情、广告等）。
*   **PluginSystem**:
    *   **安全检查**: 插件安装前的安全扫描、权限检查、依赖检查。
    *   **沙箱与隔离**: 插件运行环境的隔离机制。
    *   **健康监控**: 实时监控插件运行状态。

### 5. 通知与消息 (Notification)
*   **Notification**: 事件驱动的通知系统。
*   **Sms**: 短信模块，内置阿里云、腾讯云驱动，支持插件扩展短信通道。
*   **Email**: 邮件发送服务。

### 6. 财务与增值 (Wallet)
*   **Balance**: 用户余额管理。
*   **VirtualCoin**: 虚拟币（音符币）系统，支持充值、打赏（Donate）、消费。
*   **Points**: 积分系统，支持积分规则引擎，可根据行为触发积分变动。
*   **Coupon**: 优惠券系统。
*   **Order**: 订单模块，支撑支付与交易流程。

### 7. 系统与工具 (System & Tools)
*   **File**: 统一文件上传服务，支持本地存储及云存储扩展。
*   **Config**: 系统配置管理，支持配置分组、缓存。
*   **Report**: 举报处理模块。

## 🚀 快速开始

```bash
# 1. 安装依赖
composer install
# 或者仅安装运行时代依赖并优化自动加载
composer install --no-dev --optimize-autoloader

# 2. 配置环境
cp .env.example .env
php artisan key:generate

# 3. 配置数据库 (.env 文件)
# 执行数据库迁移与数据填充
php artisan migrate --seed

# 4. 创建存储软链接（用于存放上传的文件）
php artisan storage:link

# 5. 启动服务
php artisan serve
```

## 📂 项目结构概览

```
app/
├── Console/Commands/     # 自定义 Artisan 命令（清理缓存、初始化系统等）
├── Exceptions/           # 异常处理封装
├── Http/
│   ├── Controllers/      # 控制器基类
│   ├── Middleware/       # 中间件
│   └── Services/         # 公共 HTTP 服务（如 IP 定位）
├── Modules/              # 核心业务模块（按功能划分）
│   ├── Auth/             # 认证模块
│   ├── User/             # 用户模块
│   ├── Post/             # 内容模块
│   ├── Wallet/           # 财务模块
│   ├── PluginSystem/     # 插件系统
│   └── ...
├── Providers/            # 服务提供者
└── Repositories/         # 数据仓储模式实现
```

## 🔑 访问与配置

*   **后台管理入口**: `http://localhost:8000/admin`
*   **默认管理员**: 用户名 `admin` / 密码 `password`
*   **API 文档**: 系统集成了 Scribe，访问 `/docs` 查看接口文档。

## 📄 开源协议

本项目采用 **Apache License 2.0** 协议开源。

## 🤝 联系我们

*   **官网**: [http://www.yfsns.cn](http://www.yfsns.cn)
*   **微信**: xinghe_616

> **免责声明**: 本软件按“原样”提供，不提供任何明示或暗示的担保。使用本软件所造成的任何问题、损失或风险由使用者自行承担。