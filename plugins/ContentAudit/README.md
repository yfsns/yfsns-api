# 内容审核插件 (ContentAudit)

## API接口

### 基础路径
```
http://localhost:8000/api/v1/plugins/ContentAudit
```

### 可用接口
- `POST /audit` - 手动触发审核
- `GET /logs` - 获取审核日志
- `GET /config` - 获取配置（需要管理员权限）
- `PUT /config` - 更新配置（需要管理员权限）
- `GET /queue/status` - 获取队列状态（需要管理员权限）

### 示例请求
```bash
# 获取配置
GET /api/v1/plugins/ContentAudit/config

# 更新配置
PUT /api/v1/plugins/ContentAudit/config
Content-Type: application/json

{
  "AUDIT_API_BASE_URL": "https://api.audit.example.com",
  "AUDIT_API_TOKEN": "your-token-here",
  "AUDIT_ENABLED": true
}
```

# 内容审核插件 (ContentAudit)

内容审核插件，对接审核服务API，进行AI审核文章和动态内容。

## 功能特性

- ✅ AI审核：文章/动态发布后自动触发AI审核
- ✅ 异步审核：支持队列异步审核，不阻塞发布流程
- ✅ 审核日志：记录所有审核结果和状态
- ✅ 手动审核：提供API接口手动触发审核
- ✅ 灵活配置：支持配置审核服务API地址、密钥等

## 安装配置

### 1. 插件配置

**插件支持可视化配置界面，无需手动编辑配置文件！**

#### 可视化配置（推荐）

插件现已支持可视化配置界面，可以通过Web界面进行配置：

1. 登录管理后台
2. 进入插件管理页面
3. 点击"配置"按钮进入ContentAudit插件配置页面
4. 根据分组填写审核服务相关配置：
   - **API配置**: 审核服务API地址、令牌、超时时间
   - **审核行为**: 启用自动审核、异步审核、重试设置
   - **自动操作**: 审核结果的自动处理规则

#### 传统配置方式（兼容旧版）

如果需要手动配置，可以通过以下方式：

#### 方式一：手动创建配置文件

1. 在插件目录下创建 `.env` 文件：
```bash
# 在项目根目录执行
touch plugins/ContentAudit/.env
```

2. 编辑 `plugins/ContentAudit/.env` 文件，填写实际配置：
```env
# 审核服务API基础地址
# 本地开发: http://127.0.0.1:8001/api/tenant/ai-moderation
# Docker环境: http://host.docker.internal:8001/api/tenant/ai-moderation
# 生产环境: https://your-domain.com/api/tenant/ai-moderation
AUDIT_API_BASE_URL=http://127.0.0.1:8001/api/tenant/ai-moderation

# 审核服务API Token（Bearer Token）
AUDIT_API_TOKEN=your_api_token_here

# 请求超时时间（秒）
AUDIT_API_TIMEOUT=30

# 是否启用自动审核
AUDIT_ENABLED=true

# 是否异步审核（推荐）
AUDIT_ASYNC=true

# 审核失败重试次数
AUDIT_RETRY_TIMES=3

# 审核失败重试间隔（秒）
AUDIT_RETRY_DELAY=60

# 审核通过后是否自动发布
AUDIT_AUTO_PUBLISH_ON_PASS=false

# 审核拒绝后是否自动下架
AUDIT_AUTO_UNPUBLISH_ON_REJECT=true
```

#### 方式二：通过后台API配置（推荐）

插件提供了配置管理接口，可以通过后台界面配置：

**获取配置：**
```http
GET /api/v1/plugins/content-audit/config
Authorization: Bearer {admin_token}
```

**更新配置：**
```http
PUT /api/v1/plugins/content-audit/config
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "AUDIT_API_BASE_URL": "http://127.0.0.1:8001/api/tenant/ai-moderation",
    "AUDIT_API_TOKEN": "your_api_token_here",
    "AUDIT_API_TIMEOUT": 30,
    "AUDIT_ENABLED": true,
    "AUDIT_ASYNC": true
}
```

**注意：** 插件**不会**读取主程序的 `.env` 文件，所有配置必须存储在插件目录下的 `.env` 文件中，确保配置隔离和安全性。

### 2. 启用插件

在 `.env` 文件中添加：

```env
PLUGINS_ENABLED=ContentAudit
```

或在配置文件中：

```php
'plugins' => [
    'enabled' => ['ContentAudit'],
],
```

### 3. 运行数据库迁移

```bash
php artisan migrate
```

## API接口

### 手动触发审核

```http
POST /api/v1/plugins/content-audit/audit
Content-Type: application/json

{
    "content_id": 1,
    "content_type": "article",
    "async": true
}
```

### 获取审核日志

```http
GET /api/v1/plugins/content-audit/logs?content_id=1&content_type=article
```

## 审核服务API规范

插件会向审核服务发送以下格式的请求：

```http
POST /api/tenant/ai-moderation/check
Content-Type: application/json
Authorization: Bearer {your_api_token}

{
    "content": "文章标题 文章内容...",
    "type": "text"
}
```

审核服务返回以下格式的响应：

```json
{
    "status": "passed",
    "score": 100,
    "message": "内容审核通过",
    "details": {
        "riskLevel": "none",
        "label": "nonLabel",
        "description": "未检测出风险"
    }
}
```

状态值映射：
- `passed` → 审核通过
- `warning` → 审核通过（但可能需要人工审核）
- `rejected` → 审核拒绝

## 工作原理

### 文章审核流程

1. **用户发布文章**：
   - 用户通过前端或API发布文章
   - 文章状态自动设置为 `pending`（待审核）

2. **自动触发审核**：
   - `ArticleObserver` 监听文章状态变更
   - 当状态变为 `pending` 时，自动触发审核插件
   - 调用 `AuditService::auditContent()` 发送审核请求

3. **审核服务处理**：
   - 提取文章标题和内容文本
   - 调用外部审核服务API（`POST /api/tenant/ai-moderation/check`）
   - 传递文章内容进行审核

4. **处理审核结果**：
   - **审核通过**（`passed`）：
     - 文章状态自动更新为 `published`（已发布）
     - 保存审核结果到 `audit_result` 字段
     - 记录 `audited_at` 时间
   - **审核拒绝**（`rejected`）：
     - 文章状态更新为 `rejected`（审核拒绝）
     - 保存拒绝原因
   - **待处理**（`warning` 或其他）：
     - 保持 `pending` 状态
     - 等待人工审核

5. **记录审核日志**：
   - 保存审核记录到 `plug_contentaudit_logs` 表
   - 包含审核状态、分数、原因等详细信息

### 动态审核流程

动态（Post）的审核流程与文章类似，但状态值不同：
- `0` = 待审核
- `1` = 已发布
- `2` = 审核拒绝

## 注意事项

- 确保审核服务API可访问
- 配置正确的API密钥
- 建议使用异步审核，避免阻塞发布流程
- 定期检查审核日志，确保审核服务正常工作

