# 异步审核的几种实现方式

## 问题：发布内容时审核 API 响应慢，导致用户等待

## 方案对比

### 方案 1：队列（当前实现）✅ 推荐

**优点：**
- ✅ 完全异步，不阻塞用户请求
- ✅ 可靠性高：失败自动重试
- ✅ 可监控：查看任务状态、失败记录
- ✅ 可扩展：可以增加多个队列处理器
- ✅ Laravel 标准做法

**缺点：**
- ❌ 需要启动队列处理器（`php artisan queue:work`）
- ❌ 需要配置队列驱动（database/redis）

**适用场景：**
- 生产环境
- 需要可靠性和监控
- 审核 API 响应慢（> 3秒）

---

### 方案 2：同步但快速返回（不推荐）

**实现方式：**
```php
// Observer 中
public function created(Article $article) {
    // 立即返回，不等待审核
    // 内容状态设为 pending
    // 审核在后台慢慢进行（但仍然是同步的）
}
```

**问题：**
- ❌ 如果审核 API 超时，仍然会阻塞
- ❌ 没有重试机制
- ❌ 无法监控失败情况

---

### 方案 3：使用 Laravel 事件 + ShouldQueue

**实现方式：**
```php
// 创建事件
class ArticleCreated {
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $article;
}

// 创建监听器
class AuditArticle implements ShouldQueue {
    public function handle(ArticleCreated $event) {
        // 审核逻辑
    }
}
```

**实际上：**
- 这**就是队列**！`ShouldQueue` 接口会让监听器自动加入队列
- 和方案 1 本质上一样

---

### 方案 4：HTTP 异步请求（不推荐）

**实现方式：**
```php
// 使用 Guzzle 异步请求
$promise = $httpClient->postAsync('audit-api', [...]);
$promise->then(function ($response) {
    // 处理结果
});
```

**问题：**
- ❌ 仍然在同一个 PHP 进程中
- ❌ 如果进程被杀死，请求会丢失
- ❌ 没有持久化，无法重试
- ❌ 复杂且不可靠

---

### 方案 5：定时任务轮询（不推荐）

**实现方式：**
```php
// 每分钟检查待审核内容
Schedule::command('audit:pending')->everyMinute();
```

**问题：**
- ❌ 实时性差（最多延迟 1 分钟）
- ❌ 需要额外的定时任务配置
- ❌ 如果内容很多，可能处理不完

---

## 推荐方案

### 开发环境：
使用 **队列（database 驱动）**，简单启动：
```bash
docker-compose -f deploy/docker-compose.yml exec app php artisan queue:work
```

### 生产环境：
使用 **队列（redis 驱动）** + Supervisor 管理：
- 性能更好
- 自动重启
- 多进程处理

---

## 如果不想用队列怎么办？

### 选项 A：降低超时时间 + 快速失败
```php
// 设置短超时（如 2 秒）
'timeout' => 2,

// 如果超时，快速返回，保持 pending 状态
// 用户可以稍后手动触发审核
```

**缺点：**
- 审核可能经常失败
- 需要手动处理

### 选项 B：同步但优化用户体验
```php
// 前端显示"发布成功，正在审核中..."
// 后端立即返回，状态为 pending
// 审核在 Observer 中同步执行（但用户已经看到成功提示）
```

**缺点：**
- 如果审核失败，用户可能不知道
- 没有重试机制

---

## 结论

**在 Laravel 中，异步任务的标准做法就是使用队列。**

- 如果审核 API 很快（< 1秒），可以同步执行
- 如果审核 API 慢（> 3秒），**强烈建议使用队列**
- 队列是 Laravel 的核心功能，配置简单，可靠性高

**建议：继续使用队列方案，这是最佳实践！**

