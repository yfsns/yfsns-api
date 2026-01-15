<?php

namespace App\Modules\Notification\Strategies;

use App\Modules\Notification\Contracts\NotificationStrategy;
use App\Modules\Notification\Events\UserLoggedIn;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Notifications\Notification;

class LoginNotificationStrategy implements NotificationStrategy
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function shouldSend($event): bool
    {
        if (!$event instanceof UserLoggedIn) {
            return false;
        }

        // 登录通知默认开启（后期可添加开关控制）
        return true;
    }

    public function getNotification($event): Notification
    {
        // 准备数据
        $data = [
            'username' => $event->user->username ?? $event->user->name ?? $event->user->nickname ?? '用户',
            'login_time' => now(),
            'ip' => $event->ip,
            'location' => $event->location,
            'device' => $event->device,
        ];

        // 直接调用通知服务，传入模板代码和数据
        // 通知模块会根据模板表自动处理格式化、发送等逻辑
        $this->notificationService->send(
            $event->user,
            'user_login_success', // 模板代码
            $data,
            ['database', 'mail', 'sms'] // 通道
        );

        // 返回一个空的 Notification 对象（因为已经直接发送了）
        // NotificationDispatcher 仍然会调用 notify，但不会产生实际效果
        return new class extends Notification {
            public function via($notifiable) { 
                return []; 
            }
        };
    }

    public function getChannels(): array
    {
        return ['database', 'mail', 'sms'];
    }

    public function getEventType(): string
    {
        return UserLoggedIn::class;
    }
}
