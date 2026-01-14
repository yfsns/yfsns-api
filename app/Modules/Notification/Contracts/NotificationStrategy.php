<?php

namespace App\Modules\Notification\Contracts;

use Illuminate\Notifications\Notification;

interface NotificationStrategy
{
    /**
     * 判断是否应该发送通知
     *
     * @param mixed $event 业务事件
     * @return bool
     */
    public function shouldSend($event): bool;

    /**
     * 获取通知实例
     *
     * @param mixed $event 业务事件
     * @return Notification
     */
    public function getNotification($event): Notification;

    /**
     * 获取通知渠道
     *
     * @return array
     */
    public function getChannels(): array;

    /**
     * 获取事件类型
     *
     * @return string
     */
    public function getEventType(): string;
}
