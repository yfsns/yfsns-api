<?php

namespace App\Modules\Notification\Strategies;

use App\Modules\Notification\Contracts\NotificationStrategy;
use App\Modules\Notification\Events\PostCommented;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Notifications\Notification;

class PostCommentNotificationStrategy implements NotificationStrategy
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function shouldSend($event): bool
    {
        if (!$event instanceof PostCommented) {
            return false;
        }

        // 检查接收者是否存在
        if (!$event->receiver) {
            return false;
        }

        // 检查接收者是否开启了评论通知
        return $event->receiver->notification_settings['comment'] ?? true;
    }

    public function getNotification($event): Notification
    {
        // 准备数据
        $data = [
            'sender_name' => $event->sender->nickname ?? $event->sender->username ?? '用户',
            'post_id' => $event->post->id,
            'post_content' => mb_substr($event->post->content ?? '', 0, 100), // 限制长度
            'comment_content' => mb_substr($event->comment->content ?? '', 0, 100), // 评论内容
        ];

        // 直接调用通知服务，传入模板代码和数据
        $this->notificationService->send(
            $event->receiver,
            'post_commented', // 模板代码
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
        return PostCommented::class;
    }
}