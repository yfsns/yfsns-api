<?php

/**
 * YFSNS社交网络服务系统
 *
 * Copyright (C) 2025 合肥音符信息科技有限公司
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Modules\Notification\Channels;

use App\Modules\Notification\Models\EmailLog;
use App\Modules\Notification\Services\EmailService;
use Exception;

use function get_class;

use Illuminate\Notifications\Notification;

class EmailChannel
{
    /**
     * @var EmailService
     */
    protected $emailService;

    /**
     * 构造函数.
     */
    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * 发送通知.
     */
    public function send($notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toMail')) {
            return;
        }

        $message = $notification->toMail($notifiable);

        if (empty($message)) {
            return;
        }

        $email = $notifiable->email ?? null;
        if (! $email) {
            \Illuminate\Support\Facades\Log::warning('邮件通知发送失败：用户没有邮箱', [
                'notifiable_id' => $notifiable->id ?? null,
                'notifiable_type' => get_class($notifiable),
            ]);

            return;
        }

        $subject = $message['subject'] ?? '系统通知';
        $content = $message['data']['content'] ?? '';

        // 直接调用邮件服务发送（邮件服务只负责发送）
        $result = $this->emailService->send($email, $subject, $content);

        // 记录发送日志
        EmailLog::create([
            'email' => $email,
            'subject' => $subject,
            'content' => $content,
            'template_id' => null,
            'template_data' => $message['data'] ?? [],
            'status' => $result ? 1 : 0,
            'error_message' => $result ? null : '发送失败',
        ]);
    }
}
