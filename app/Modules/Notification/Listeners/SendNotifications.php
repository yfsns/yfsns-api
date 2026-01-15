<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\Notification\Services\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendNotifications implements ShouldQueue
{
    protected NotificationDispatcher $dispatcher;

    /**
     * 队列配置
     */
    public string $queue = 'notifications';
    public int $delay = 5; // 延迟5秒发送，避免用户体验卡顿

    public function __construct(NotificationDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * 处理事件
     */
    public function handle($event): void
    {
        $eventClass = get_class($event);

        Log::info('开始处理通知事件', [
            'event_type' => $eventClass,
            'queue' => $this->queue,
            'delay' => $this->delay
        ]);

        try {
            $this->dispatcher->handle($event);

            Log::info('通知处理完成', [
                'event_type' => $eventClass
            ]);
        } catch (\Exception $e) {
            Log::error('通知处理失败', [
                'event_type' => $eventClass,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // 可以选择重试或发送告警
            throw $e;
        }
    }

    /**
     * 处理失败时的回调
     */
    public function failed($event, \Throwable $exception): void
    {
        Log::error('通知队列处理失败', [
            'event_type' => get_class($event),
            'error' => $exception->getMessage()
        ]);

        // 可以发送告警通知给管理员
    }
}
