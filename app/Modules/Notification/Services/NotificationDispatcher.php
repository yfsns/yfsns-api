<?php

namespace App\Modules\Notification\Services;

use App\Modules\Notification\Contracts\NotificationStrategy;
use Illuminate\Support\Facades\Log;

class NotificationDispatcher
{
    private array $strategies;

    public function __construct(array $strategies)
    {
        $this->strategies = $strategies;
    }

    /**
     * 处理事件并分发通知
     */
    public function handle($event): void
    {
        $eventClass = get_class($event);

        Log::debug('NotificationDispatcher 处理事件', [
            'event_type' => $eventClass,
            'strategies_count' => count($this->strategies)
        ]);

        foreach ($this->strategies as $strategy) {
            try {
                if ($strategy->shouldSend($event)) {
                    $notification = $strategy->getNotification($event);
                    $channels = $strategy->getChannels();

                    // 获取接收者
                    $receiver = $this->getReceiverFromEvent($event);

                    if ($receiver) {
                        Log::debug('发送通知', [
                            'receiver_id' => $receiver->id,
                            'notification' => get_class($notification),
                            'channels' => $channels
                        ]);

                        $receiver->notify($notification, $channels);
                    }
                }
            } catch (\Exception $e) {
                Log::error('通知分发失败', [
                    'event' => $eventClass,
                    'strategy' => get_class($strategy),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * 从事件中获取接收者
     */
    private function getReceiverFromEvent($event)
    {
        // 尝试不同的属性名来获取接收者
        if (property_exists($event, 'receiver')) {
            return $event->receiver;
        }

        if (property_exists($event, 'user')) {
            return $event->user;
        }

        return null;
    }

    /**
     * 添加新的通知策略
     */
    public function addStrategy(NotificationStrategy $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    /**
     * 移除通知策略
     */
    public function removeStrategy(string $strategyClass): void
    {
        $this->strategies = array_filter($this->strategies, function ($strategy) use ($strategyClass) {
            return get_class($strategy) !== $strategyClass;
        });
    }
}
