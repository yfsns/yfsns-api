# 审核模块使用指南

## 通用可扩展审核模块

基于多态关联和Trait的审核系统，支持各种内容类型的审核，具有良好的扩展性和易用性。

### 核心优势
-  **多态关联** - 支持任意模型的审核
-  **Trait简化** - 模型只需use HasReviewable即可获得审核功能
-  **配置驱动** - 易于添加新内容类型
-  **统一日志** - 所有审核记录集中管理
-  **简单易用** - API直观，学习成本低

## 📋 核心组件

### 1. HasReviewable Trait - 为模型添加审核功能

```php
use App\Modules\Review\Traits\HasReviewable;

// 在你的模型中使用
class Post extends Model
{
    use HasReviewable;

    // 现在你的模型拥有了审核功能！
    // $post->approve('优质内容');
    // $post->reject('违规内容');
    // $post->isApproved(); // 检查是否已审核通过
    // $post->reviewLogs(); // 获取审核历史
}
```

#### Trait提供的方法

```php
// 审核操作
$post->approve('审核备注');           // 审核通过
$post->reject('拒绝原因');            // 审核拒绝

// 状态检查
$post->isApproved();                  // 是否已通过
$post->isRejected();                  // 是否已拒绝
$post->isPending();                   // 是否待审核
$post->getReviewStatus();             // 获取当前状态

// 审核历史
$post->reviewLogs();                  // 获取所有审核日志
$post->latestReviewLog();             // 获取最新审核日志
$post->getReviewStats();              // 获取审核统计
```

### 2. ReviewService - 核心审核服务

```php
use App\Modules\Review\Services\ReviewService;

$service = app(ReviewService::class);

// 人工审核
$log = $service->manualReview($post, 'approve', '优质内容', $adminId, [
    'reason' => 'quality',
    'tags' => ['优质内容'],
    'score' => 95
]);

// 批量审核
$results = $service->batchManualReview([
    ['model' => $post1, 'action' => 'approve', 'remark' => '通过'],
    ['model' => $post2, 'action' => 'reject', 'remark' => '拒绝']
], $adminId);

// 获取审核统计
$stats = $service->getReviewStats('post', '2024-01-01', '2024-12-31');
// 返回：总审核数、通过数、拒绝数、通过率等
```

### 3. ReviewLog 模型 - 审核日志

```php
use App\Modules\Review\Models\ReviewLog;

// 查询审核日志
$logs = ReviewLog::approved()->get();              // 已通过的
$logs = ReviewLog::rejected()->get();              // 已拒绝的
$logs = ReviewLog::manual()->get();                // 人工审核的
$logs = ReviewLog::ai()->get();                    // AI审核的
$logs = ReviewLog::byAdmin(1)->get();              // 指定管理员的
$logs = ReviewLog::byContentType('post')->get();   // 指定内容类型的
$logs = ReviewLog::dateRange('2024-01-01', '2024-12-31')->get(); // 时间范围

// 工具方法
$log->isApproved();                    // 是否通过
$log->isRejected();                    // 是否拒绝
$log->getResultDescription();          // 获取结果描述
$log->getStatusChangeDescription();    // 获取状态变更描述
```

## 使用场景示例

### 场景1：模型中直接审核

```php
// Post.php
class Post extends Model
{
    use HasReviewable;
}

// 使用时
$post = Post::find(1);

// 审核通过
$log = $post->approve('内容优质，予以通过');

// 审核拒绝
$log = $post->reject('包含违规内容');

// 检查状态
if ($post->isApproved()) {
    // 已通过审核
}

// 获取审核历史
$logs = $post->reviewLogs()->with('admin')->get();
```

### 场景2：控制器中的审核操作

```php
// Admin/PostController.php
class PostController extends Controller
{
    public function approve(Request $request, Post $post)
    {
        try {
            $log = app(ReviewService::class)->manualReview(
                $post,
                'approve',
                $request->remark,
                auth()->id(),
                $request->extra_data
            );

            return response()->json([
                'message' => '审核通过',
                'log' => $log,
                'post' => $post->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

### 场景3：批量审核

```php
// Admin/ReviewController.php
class ReviewController extends Controller
{
    public function batchApprove(Request $request)
    {
        $posts = Post::whereIn('id', $request->post_ids)->get();

        $reviews = $posts->map(function ($post) {
            return [
                'model' => $post,
                'action' => 'approve',
                'remark' => '批量通过',
                'extra_data' => ['batch' => true]
            ];
        });

        $results = app(ReviewService::class)->batchManualReview($reviews, auth()->id());

        return response()->json([
            'message' => '批量审核完成',
            'results' => $results
        ]);
    }
}
```


### 场景5：插件扩展审核功能

```php
// 自定义审核插件
class CustomReviewPlugin
{
    public function review(Model $content, array $rules): ReviewLog
    {
        // 自定义审核逻辑
        $result = $this->customReviewLogic($content, $rules);

        return app(ReviewService::class)->manualReview(
            $content,
            $result['action'],
            $result['remark'],
            $result['admin_id'],
            ['plugin' => 'custom', 'rules' => $rules]
        );
    }
}
```

## 配置和扩展

### 添加新的内容类型

```php
// ReviewService.php 中添加配置
protected array $contentTypes = [
    'article' => [
        'model' => \App\Modules\Article\Models\Article::class,
        'pending_status' => 'pending',
    ],
    'post' => [
        'model' => \App\Modules\Post\Models\Post::class,
        'pending_status' => 0,
    ],
    'thread' => [
        'model' => \App\Modules\Forum\Models\ForumThread::class,
        'pending_status' => 'pending',
    ],
    // 添加新的内容类型
    'video' => [
        'model' => \App\Modules\Video\Models\Video::class,
        'pending_status' => 'draft',
    ],
];

// Video.php
class Video extends Model
{
    use HasReviewable; // 自动获得审核功能
}
```

### 自定义审核逻辑

```php
// 创建自定义审核服务
class CustomReviewService extends ReviewService
{
    public function customReview(Model $model, array $params): ReviewLog
    {
        // 自定义审核逻辑
        $result = $this->validateContent($model, $params);

        return $this->manualReview(
            $model,
            $result['action'],
            $result['remark'],
            $params['admin_id'] ?? null,
            array_merge($params, ['custom' => true])
        );
    }
}
```

## API参考

### ReviewService 方法

```php
// 核心审核方法
manualReview(Model $model, string $action, ?string $remark = null, ?int $adminId = null, ?array $extraData = null): ReviewLog

// 批量审核
batchManualReview(array $reviews, ?int $adminId = null): array

// 获取审核统计
getReviewStats(?string $contentType = null, ?string $dateFrom = null, ?string $dateTo = null): array

// 获取待审核内容
getPendingContents(?string $contentType = null, int $limit = 100, int $offset = 0): array
getPendingCount(?string $contentType = null): array

// 获取审核日志
getLogs(Model $model, ?string $channel = null): Collection
getLatestLog(Model $model, ?string $channel = null): ?ReviewLog
```

### ReviewController API

```php
// 单项审核
POST /admin/review/manual
{
    "content_type": "post",
    "content_id": 1,
    "status": "published",
    "remark": "审核备注",
    "extra_data": {"reason": "quality"}
}

// 批量审核
POST /admin/review/batch
{
    "reviews": [
        {
            "content_type": "post",
            "content_id": 1,
            "action": "approve",
            "remark": "通过"
        }
    ]
}

// 获取审核统计
GET /admin/review/stats?content_type=post&date_from=2024-01-01&date_to=2024-12-31

// 获取待审核内容
GET /admin/review/pending?content_type=post&limit=50&offset=0
```

## 设计原则

1. **简单性** - API直观，易于理解和使用
2. **扩展性** - 易于添加新的内容类型和审核逻辑
3. **一致性** - 所有内容类型的审核行为保持一致
4. **可追溯** - 完整的审核日志记录
5. **解耦合** - 审核逻辑与业务逻辑分离

## 最佳实践

1. **统一使用HasReviewable Trait** - 为所有可审核模型添加该Trait
2. **集中管理审核配置** - 在ReviewService中统一配置内容类型
3. **使用extra_data扩展** - 自定义审核参数通过extra_data传递
4. **记录详细日志** - 充分利用ReviewLog记录审核详情
5. **批量操作优化** - 大量审核时使用批量API

```php
// 推荐：在模型中
class Article extends Model
{
    use HasReviewable;
}

// 推荐：在控制器中
public function approve(Model $content)
{
    return $content->approve($request->remark);
}

// 推荐：在Service中
public function reviewContent(Model $content)
{
    // 业务逻辑判断是否通过审核
    if ($this->shouldApprove($content)) {
        return $content->approve('审核通过');
    } else {
        return $content->reject('审核失败');
    }
}
```

这种基于Trait和多态关联的审核架构既保持了Laravel的优雅，又具备了强大的扩展性和易用性！
