<?php

namespace Plugins\ContentAudit\Listeners;

use App\Modules\Review\Events\ContentPendingAudit;
use Exception;
use Illuminate\Support\Facades\Log;
use Plugins\ContentAudit\Jobs\ProcessPendingAuditsJob;

/**
 * 内容待审核事件监听器.
 *
 * 当内容创建后状态为待审核时，触发此监听器
 * 监听器会分发队列任务进行异步审核
 */
class ContentPendingAuditListener
{
    /**
     * 处理内容待审核事件.
     */
    public function handle(ContentPendingAudit $event): void
    {
        Log::info('ContentAudit 事件监听器被调用', [
            'content_type' => $event->contentType,
            'content_id' => $event->contentId,
        ]);

        try {
            // 检查插件是否启用
            $pluginManager = app('plugin.manager');
            if (! $pluginManager) {
                Log::warning('ContentAudit: plugin.manager 未绑定', [
                    'content_type' => $event->contentType,
                    'content_id' => $event->contentId,
                ]);

                return;
            }

            $isEnabled = $pluginManager->isPluginEnabled('ContentAudit');
            Log::info('ContentAudit: 插件启用状态检查', [
                'is_enabled' => $isEnabled,
                'content_type' => $event->contentType,
                'content_id' => $event->contentId,
            ]);

            if (! $isEnabled) {
                Log::debug('ContentAudit 插件未启用，跳过处理', [
                    'content_type' => $event->contentType,
                    'content_id' => $event->contentId,
                ]);

                return;
            }

            // 分发审核任务到队列
            Log::info('ContentAudit: 准备分发审核任务', [
                'content_type' => $event->contentType,
                'content_id' => $event->contentId,
            ]);

            ProcessPendingAuditsJob::dispatch(
                $event->contentType,
                $event->contentId
            );

            Log::info('ContentAudit 插件监听到内容待审核事件', [
                'content_type' => $event->contentType,
                'content_id' => $event->contentId,
            ]);
        } catch (Exception $e) {
            Log::error('ContentAudit 插件处理待审核事件失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'content_type' => $event->contentType,
                'content_id' => $event->contentId,
            ]);
        }
    }
}
