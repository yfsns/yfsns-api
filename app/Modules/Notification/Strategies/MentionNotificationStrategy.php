<?php

namespace App\Modules\Notification\Strategies;

use App\Modules\Notification\Contracts\NotificationStrategy;
use App\Modules\Notification\Events\UserMentioned;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Notifications\Notification;

class MentionNotificationStrategy implements NotificationStrategy
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function shouldSend($event): bool
    {
        if (!$event instanceof UserMentioned) {
            return false;
        }

        // 检查接收者是否存在
        if (!$event->receiver) {
            return false;
        }

        // 检查接收者是否开启了@通知
        return $event->receiver->notification_settings['mention'] ?? true;
    }

    public function getNotification($event): Notification
    {
        // 准备数据
        $data = [
            'sender_name' => $event->sender->nickname ?? $event->sender->username ?? '用户',
            'post_id' => $event->post->id,
            'post_content' => mb_substr($event->post->content ?? '', 0, 100), // 限制长度
        ];

        // 直接调用通知服务，传入模板代码和数据
        $this->notificationService->send(
            $event->receiver,
            'user_mentioned', // 模板代码
            $data,
            ['database'] // 通道：database（数据库存储）
        );

        // 返回一个空的 Notification 对象（因为已经直接发送了）
        return new class extends Notification {
            public function via($notifiable) { 
                return []; 
            }
        };
    }

    public function getChannels(): array
    {
        return ['database'];
    }

    public function getEventType(): string
    {
        return UserMentioned::class;
    }
}
