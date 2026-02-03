<?php

namespace Plugins\ContentAudit\Jobs;

// 暂时注释掉，待重新搭建ContentAudit后启用
// use App\Modules\Plugin\Contracts\PendingContentProviderInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 处理待审核任务
 *
 * 完全依赖事件驱动，只处理事件触发的特定内容
 * 通过统一接口获取待审核内容，并分发审核任务
 *
 * 使用 ShouldBeUnique 确保同一内容的审核任务不会重复执行
 */
class ProcessPendingAuditsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 唯一锁的过期时间（秒）
     * 如果任务执行时间超过此时间，锁会自动释放，允许重新执行.
     */
    public int $uniqueFor = 60; // 1分钟

    /**
     * 内容类型.
     */
    protected string $contentType;

    /**
     * 内容ID.
     */
    protected int $contentId;

    /**
     * 创建任务实例.
     */
    public function __construct(string $contentType, int $contentId)
    {
        $this->contentType = $contentType;
        $this->contentId = $contentId;
        $this->onQueue('audit');
    }

    /**
     * 获取任务的唯一标识（用于 ShouldBeUnique）
     * 确保同一内容的审核任务不会重复执行.
     */
    public function uniqueId(): string
    {
        return "process_pending_audits_{$this->contentType}_{$this->contentId}";
    }

    /**
     * 执行任务
     */
    public function handle(PendingContentProviderInterface $provider): void
    {
        Log::info('ProcessPendingAuditsJob 开始处理', [
            'content_type' => $this->contentType,
            'content_id' => $this->contentId,
        ]);

        // 检查是否应该审核该类型的内容
        if (! $this->shouldAuditContentType($this->contentType)) {
            Log::info('ProcessPendingAuditsJob: 该内容类型未开启审核，跳过处理', [
                'content_type' => $this->contentType,
                'content_id' => $this->contentId,
            ]);
            return;
        }

        // 获取指定的待审核内容
        $content = $provider->getPendingContent($this->contentType, $this->contentId);

        if (! $content) {
            Log::info('待审核内容不存在或已被处理', [
                'content_type' => $this->contentType,
                'content_id' => $this->contentId,
            ]);

            return;
        }

        Log::info('ProcessPendingAuditsJob 找到待审核内容，准备分发审核任务', [
            'content_type' => $this->contentType,
            'content_id' => $this->contentId,
        ]);

        // 分发审核任务
        $this->dispatchAuditJob($content);
    }

    /**
     * 分发审核任务
     */
    protected function dispatchAuditJob(array $content): void
    {
        try {
            // 提取内容数据
            $contentData = [
                'id' => $content['content_id'],
                'title' => $content['title'] ?? '',
                'content' => $content['content'] ?? '',
                'description' => $content['description'] ?? $content['excerpt'] ?? '',
            ];

            Log::info('ProcessPendingAuditsJob: 准备分发 AuditContentJob', [
                'content_type' => $content['content_type'],
                'content_id' => $content['content_id'],
                'content_data' => $contentData,
            ]);

            // 分发审核任务
            AuditContentJob::dispatch(
                $content['content_type'],
                $content['content_id'],
                $contentData
            );

            Log::info('ProcessPendingAuditsJob: AuditContentJob 已分发', [
                'content_type' => $content['content_type'],
                'content_id' => $content['content_id'],
            ]);
        } catch (Exception $e) {
            Log::error('分发审核任务失败', [
                'content_type' => $content['content_type'] ?? null,
                'content_id' => $content['content_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 检查是否应该审核该类型的内容
     */
    protected function shouldAuditContentType(string $contentType): bool
    {
        // 根据内容类型确定配置键名
        $configKeyMap = [
            'article' => 'review_article',
            'post' => 'review_post',
            'comment' => 'review_comment',
            'forum_thread' => 'review_article', // 暂时使用文章的审核设置
            'topic' => 'review_post', // 暂时使用动态的审核设置
        ];

        $configKey = $configKeyMap[$contentType] ?? null;
        if (! $configKey) {
            Log::warning('ProcessPendingAuditsJob: 未知的内容类型', [
                'content_type' => $contentType,
            ]);
            return false;
        }

        // 从数据库获取配置值
        try {
            $setting = \DB::table('plug_contentaudit_settings')
                ->where('plugin_name', 'ContentAudit')
                ->where('key', $configKey)
                ->first();

            if (! $setting) {
                Log::warning('ProcessPendingAuditsJob: 未找到审核配置', [
                    'content_type' => $contentType,
                    'config_key' => $configKey,
                ]);
                return false;
            }

            // 布尔值判断：1、'1'、'true'、true 都认为是true
            $isEnabled = in_array($setting->value, ['1', 'true', 1, true], true);

            Log::info('ProcessPendingAuditsJob: 审核配置检查结果', [
                'content_type' => $contentType,
                'config_key' => $configKey,
                'config_value' => $setting->value,
                'is_enabled' => $isEnabled,
            ]);

            return $isEnabled;
        } catch (\Exception $e) {
            Log::error('ProcessPendingAuditsJob: 检查审核配置失败', [
                'content_type' => $contentType,
                'config_key' => $configKey,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
