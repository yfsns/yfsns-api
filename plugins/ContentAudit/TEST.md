# 审核插件测试指南

## 测试步骤

### 1. 配置审核服务地址

编辑 `plugins/ContentAudit/.env` 文件，设置正确的审核服务地址：

```env
# 如果审核服务在公网
AUDIT_API_BASE_URL=https://your-audit-service.com/api/tenant/ai-moderation

# 如果审核服务在同一服务器（Docker环境）
AUDIT_API_BASE_URL=http://host.docker.internal:8001/api/tenant/ai-moderation

# 如果审核服务在同一Docker网络
AUDIT_API_BASE_URL=http://audit-service:8001/api/tenant/ai-moderation
```

### 2. 配置 API Token

```env
AUDIT_API_TOKEN=your_api_token_here
```

### 3. 测试连接

```bash
# 在Docker容器中测试
docker exec yfsns-laravel php artisan tinker --execute="
\$article = \App\Modules\Article\Models\Article::first();
\$auditService = app(\Plugins\ContentAudit\Services\AuditService::class);
\$result = \$auditService->auditContent(\$article, 'article', false);
var_dump(\$result);
"
```

### 4. 通过API接口测试

```http
POST /api/v1/plugins/content-audit/audit
Authorization: Bearer {token}
Content-Type: application/json

{
    "content_id": 1,
    "content_type": "article",
    "async": false
}
```

## 网络配置说明

### Docker 环境网络配置

如果审核服务在宿主机运行：
- 使用 `host.docker.internal:8001`（Mac/Windows Docker Desktop）
- 使用宿主机IP地址（Linux Docker）

如果审核服务在公网：
- 直接使用公网地址：`https://your-domain.com`

### 常见问题

1. **连接失败：** 检查审核服务是否运行，网络是否可达
2. **401 未授权：** 检查 API Token 是否正确
3. **超时：** 检查网络延迟，适当增加 `AUDIT_API_TIMEOUT`

