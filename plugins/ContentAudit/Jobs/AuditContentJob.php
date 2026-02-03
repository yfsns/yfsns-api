<?php

namespace Plugins\ContentAudit\Jobs;

// 暂时注释掉，待重新搭建ContentAudit后启用
// use App\Modules\Plugin\Contracts\ContentStatusUpdaterInterface;
use Exception;

use function get_class;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use function in_array;

use Plugins\ContentAudit\Services\AuditService;
use Throwable;

/**
 * 内容审核任务（异步）.
 *
 * 将审核流程放到队列中异步执行，避免阻塞用户发布请求
 * 使用 ShouldBeUnique 确保同一内容的审核任务不会重复执行
 */
class AuditContentJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务超时时间（秒）.
     */
    public $timeout = 120;

    /**
     * 任务重试次数
     * 设置为较大值，让任务可以多次重试，等待 API 恢复.
     */
    public $tries = 10;

    /**
     * 重试延迟（秒）
     * 使用指数退避策略：第1次重试等待60秒，第2次120秒，第3次240秒...
     */
    public $backoff = [60, 120, 240, 480, 960];

    /**
     * 唯一锁的过期时间（秒）
     * 如果任务执行时间超过此时间，锁会自动释放，允许重新执行.
     */
    public int $uniqueFor = 300; // 5分钟

    /**
     * 内容类型.
     */
    protected string $contentType;

    /**
     * 内容ID.
     */
    protected int $contentId;

    /**
     * 内容数据.
     */
    protected array $contentData;

    /**
     * 创建任务实例.
     */
    public function __construct(string $contentType, int $contentId, array $contentData)
    {
        $this->contentType = $contentType;
        $this->contentId = $contentId;
        $this->contentData = $contentData;

        // 使用专用队列 'audit'
        $this->onQueue('audit');
    }

    /**
     * 获取任务的唯一标识（用于 ShouldBeUnique）
     * 确保同一内容的审核任务不会重复执行.
     */
    public function uniqueId(): string
    {
        return "audit_{$this->contentType}_{$this->contentId}";
    }

    /**
     * 执行任务
     */
    public function handle(
        AuditService $auditService,
        ContentStatusUpdaterInterface $statusUpdater
    ): void {
        Log::info('AuditContentJob 开始执行', [
            'content_type' => $this->contentType,
            'content_id' => $this->contentId,
            'content_data' => $this->contentData,
        ]);

        try {
            // 1. 调用审核服务
            Log::info('AuditContentJob: 调用审核服务', [
                'content_type' => $this->contentType,
                'content_id' => $this->contentId,
            ]);
            $auditResult = $auditService->auditContent($this->contentData, $this->contentType);

            Log::info('AuditContentJob: 审核服务返回结果', [
                'content_type' => $this->contentType,
                'content_id' => $this->contentId,
                'audit_result' => $auditResult,
            ]);

            // 2. 根据审核结果确定新状态（接口会自动处理状态转换）
            $newStatus = $this->mapAuditResultToStatus($auditResult);

            Log::info('AuditContentJob: 映射审核结果到状态', [
                'content_type' => $this->contentType,
                'content_id' => $this->contentId,
                'audit_result' => $auditResult,
                'new_status' => $newStatus,
            ]);

            // 3. 调用接口更新状态和记录日志（接口统一处理，插件只需传递参数）
            Log::info('AuditContentJob: 更新状态和记录日志', [
                'content_type' => $this->contentType,
                'content_id' => $this->contentId,
                'new_status' => $newStatus,
            ]);
            $statusUpdater->updateStatusAndLog(
                $this->contentType,
                $this->contentId,
                $newStatus,
                'ai',
                'ContentAudit',
                $auditResult
            );

            Log::info('AuditContentJob 执行完成', [
                'content_type' => $this->contentType,
                'content_id' => $this->contentId,
                'new_status' => $newStatus,
            ]);
        } catch (Exception $e) {
            Log::warning('审核任务失败，将重试', [
                'content_type' => $this->contentType,
                'content_id' => $this->contentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * 任务失败时的处理（重试多次后仍然失败）.
     *
     * 注意：只有在所有重试都失败后才会调用此方法
     * 此时可以记录错误日志，或者将任务放入失败队列等待手动重试
     */
    public function failed(Throwable $exception): void
    {
        $attempts = $this->attempts();

        Log::error('审核任务最终失败（已重试 ' . $attempts . ' 次）', [
            'content_type' => $this->contentType,
            'content_id' => $this->contentId,
            'error' => $exception->getMessage(),
            'exception' => get_class($exception),
            'total_attempts' => $attempts,
        ]);

        // 只有在多次重试都失败后，才记录错误日志
        try {
            $statusUpdater = app(ContentStatusUpdaterInterface::class);
            $model = $this->getContentModel();

            if ($model) {
                $errorMessage = $this->formatErrorMessage($exception);

                $errorResult = [
                    'status' => 'error',
                    'message' => $errorMessage . '（已重试 ' . $attempts . ' 次，可在失败任务中手动重试）',
                    'error' => true,
                    'timestamp' => now()->toIso8601String(),
                    'retries' => $attempts,
                ];

                // 记录最终失败的审核日志
                $statusUpdater->logReview(
                    $this->contentType,
                    $this->contentId,
                    'ai',
                    $model->status,
                    $model->status, // 状态不变
                    'ContentAudit',
                    $errorResult,
                    $errorMessage
                );
            }
        } catch (Exception $e) {
            Log::error('保存审核错误信息失败', [
                'content_type' => $this->contentType,
                'content_id' => $this->contentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 格式化错误信息.
     */
    protected function formatErrorMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();

        // 提供友好的错误提示
        if (str_contains($message, 'timeout') || str_contains($message, 'Connection timed out')) {
            return '审核服务响应超时';
        }

        if (str_contains($message, 'Connection refused')) {
            return '审核服务连接被拒绝';
        }

        if (str_contains($message, 'Could not resolve host')) {
            return '无法解析审核服务地址';
        }

        return '审核服务异常: ' . $message;
    }

    /**
     * 获取内容模型.
     */
    protected function getContentModel()
    {
        $modelClass = match ($this->contentType) {
            'article' => \App\Modules\Article\Models\Article::class,
            'post' => \App\Modules\Post\Models\Post::class,
            'thread' => \App\Modules\Forum\Models\ForumThread::class,
            'comment' => \App\Modules\Comment\Models\Comment::class,
            'topic' => \App\Modules\Topic\Models\Topic::class,
            default => null,
        };

        if (! $modelClass) {
            return null;
        }

        return $modelClass::find($this->contentId);
    }

    /**
     * 将审核结果映射为内容状态
     *
     * 对于 Post、Comment 和 Topic，直接返回数字状态（1=已发布，2=已拒绝，0=待审核）
     * 对于 Article 和 ForumThread，返回字符串状态
     */
    protected function mapAuditResultToStatus(array $auditResult)
    {
        $status = $auditResult['status'] ?? 'pending';

        // 对于 Post、Comment 和 Topic，直接返回数字状态，避免字符串转换问题
        if (in_array($this->contentType, ['post', 'comment', 'topic'], true)) {
            return match ($status) {
                'pass', 'approved' => 1,  // STATUS_PUBLISHED
                'reject', 'rejected' => 2, // STATUS_REJECTED
                default => 0,              // STATUS_PENDING
            };
        }

        // 对于 Article 和 ForumThread，返回字符串状态
        return match ($status) {
            'pass', 'approved' => 'published',
            'reject', 'rejected' => 'rejected',
            default => 'pending',
        };
    }
}
