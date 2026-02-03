# 异步审核说明

## 问题背景

之前的审核流程是**同步执行**的，导致以下问题：
- 用户发布内容时需要等待审核 API 响应（可能 30 秒或更久）
- 如果审核 API 超时或失败，用户会等待很久才能看到结果
- 用户体验差，发布按钮卡住

## 解决方案

现在审核流程已改为**异步执行**：
1. 用户发布内容 → 立即返回成功
2. 内容状态为"待审核"
3. 审核任务加入队列，后台异步执行
4. 审核完成后自动更新内容状态

## 配置队列

### 1. 检查队列配置

确保 `.env` 中配置了队列连接：

```env
QUEUE_CONNECTION=database
```

或者使用 Redis（推荐，性能更好）：

```env
QUEUE_CONNECTION=redis
```

### 2. 启动队列处理器

**开发环境（Docker）：**

```bash
docker-compose -f deploy/docker-compose.yml exec app php artisan queue:work
```

**生产环境：**

使用 Supervisor 管理队列进程，配置示例：

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/worker.log
stopwaitsecs=3600
```

### 3. 监控队列

查看队列中的任务：

```bash
php artisan queue:monitor
```

查看失败的任务：

```bash
php artisan queue:failed
```

重试失败的任务：

```bash
php artisan queue:retry all
```

## 工作流程

### 发布内容时：

1. **用户提交** → 内容保存，状态为 `pending`（待审核）
2. **Observer 触发** → 将审核任务加入队列
3. **立即返回** → 用户看到"发布成功"，内容处于待审核状态

### 后台审核：

1. **队列处理器** → 从队列取出审核任务
2. **调用审核 API** → 审核内容
3. **更新状态** → 
   - 通过 → `published`（已发布）
   - 拒绝 → `rejected`（已拒绝）
   - 失败 → 保持 `pending`，可重试

## 任务配置

- **超时时间**：120 秒
- **重试次数**：3 次
- **失败处理**：记录日志，内容保持待审核状态

## 优势

✅ **用户体验好**：发布立即返回，无需等待  
✅ **可靠性高**：失败自动重试  
✅ **可扩展**：可以轻松增加队列处理器数量  
✅ **可监控**：可以查看任务状态和失败记录

## 注意事项

**必须启动队列处理器**，否则审核任务不会执行！

如果队列处理器未运行：
- 内容会一直处于"待审核"状态
- 需要手动触发审核或启动队列处理器

## 手动触发审核

如果队列未运行，可以通过以下方式手动触发：

1. **通过统一审核中心**：`/api/admin/review/ai`
2. **通过插件接口**：`/api/plugin/content-audit/audit`

