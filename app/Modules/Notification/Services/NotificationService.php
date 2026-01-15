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

namespace App\Modules\Notification\Services;

use App\Modules\Notification\Models\NotificationSetting;
use App\Modules\Notification\Models\NotificationTemplate;

use function get_class;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

use function is_array;

class NotificationService
{
    /**
     * 发送通知.
     *
     * @param mixed  $notifiable   通知对象
     * @param string $templateCode 模板代码
     * @param array  $data         模板数据
     * @param array  $channels     指定通道
     */
    public function send($notifiable, string $templateCode, array $data = [], array $channels = []): void
    {
        // 获取模板
        $template = NotificationTemplate::where('code', $templateCode)
            ->where('status', true)
            ->first();

        if (! $template) {
            return;
        }

        // 如果 $notifiable 是数组，尝试获取用户对象
        if (is_array($notifiable)) {
            $notifiable = \App\Modules\User\Models\User::find($notifiable['id']);
            if (! $notifiable) {
                return;
            }
        }

        // 获取用户通知设置
        $settings = NotificationSetting::where('notifiable_type', get_class($notifiable))
            ->where('notifiable_id', $notifiable->id)
            ->where('type', $template->type)
            ->first();

        // 确定使用的通道
        $enabledChannels = $channels ?: ($settings ? $settings->channels : $template->channels);

        // 发送通知
        foreach ($enabledChannels as $channel) {
            if (! $template->supportsChannel($channel)) {
                continue;
            }

            if ($settings && ! $settings->isChannelEnabled($channel)) {
                continue;
            }

            $this->sendViaChannel($notifiable, $template, $channel, $data);
        }
    }

    /**
     * 通过指定通道发送通知.
     */
    protected function sendViaChannel($notifiable, NotificationTemplate $template, string $channel, array $data): void
    {
        if ($channel === 'sms') {
            $this->sendSmsNotificationViaChannel($notifiable, $template, $data);

            return;
        }

        if ($channel === 'mail') {
            $this->sendEmailNotificationViaChannel($notifiable, $template, $data);

            return;
        }

        // 其他通道（database 对应 Laravel 的 database 通道）
        $content = $template->getContent($channel);

        if (empty($content)) {
            return;
        }

        // 替换模板变量
        $content = $this->replaceVariables($content, $data);

        // 创建通知
        // database 渠道使用 Laravel 的 database 通道保存到数据库
        $notification = new class($content) extends Notification
        {
            protected $content;

            public function __construct($content)
            {
                $this->content = $content;
            }

            public function via($notifiable)
            {
                return ['database'];
            }

            public function toDatabase($notifiable)
            {
                return [
                    'message' => $this->content,
                    'data' => [],
                ];
            }
        };

        // 发送通知
        NotificationFacade::send($notifiable, $notification);
    }

    /**
     * 通过Email通道发送通知.
     */
    protected function sendEmailNotificationViaChannel($notifiable, NotificationTemplate $template, array $data): void
    {
        $content = $template->getContent('mail');

        if (empty($content)) {
            return;
        }

        // 替换模板变量
        $content = $this->replaceVariables($content, $data);

        // 创建邮件通知
        $notification = new class($template->code, $template->name, $content) extends Notification
        {
            protected $templateCode;

            protected $templateName;

            protected $content;

            public function __construct(string $templateCode, string $templateName, string $content)
            {
                $this->templateCode = $templateCode;
                $this->templateName = $templateName;
                $this->content = $content;
            }

            public function via($notifiable)
            {
                return ['email'];
            }

            public function toMail($notifiable)
            {
                return [
                    'template' => $this->templateCode,
                    'subject' => $this->templateName,
                    'data' => [
                        'content' => $this->content,
                    ],
                ];
            }
        };

        // 通过Laravel通知系统发送，会自动路由到EmailChannel
        NotificationFacade::send($notifiable, $notification);
    }

    /**
     * 通过SMS通道发送通知.
     */
    protected function sendSmsNotificationViaChannel($notifiable, NotificationTemplate $template, array $data): void
    {
        \Illuminate\Support\Facades\Log::info('SMS notification sending started', [
            'template_code' => $template->code,
            'sms_template_id' => $template->sms_template_id,
            'user_phone' => $notifiable->phone ?? 'no phone',
            'data_keys' => array_keys($data)
        ]);

        // 优先使用NotificationTemplate中的sms_template_id
        // 但是传递给SmsChannel的template应该是原始模板代码，让SmsChannel自己查找模板并获取sms_template_id
        if ($template->sms_template_id) {
            // 传递原始模板代码，SmsChannel会查找NotificationTemplate并获取sms_template_id
            $smsTemplateCode = $template->code; // 使用原始模板代码，不是sms_template_id
            $smsTemplateData = $data;
            \Illuminate\Support\Facades\Log::info('Using NotificationTemplate', [
                'template_code' => $template->code,
                'sms_template_id' => $template->sms_template_id
            ]);
        } else {
            // 后备方案：查找SmsTemplate表
            $smsTemplateCode = $template->code; // 尝试使用通知模板代码作为SMS模板代码

            // 检查是否存在对应的SMS模板
            $smsTemplate = \App\Modules\Sms\Models\SmsTemplate::where('code', $smsTemplateCode)
                ->where('status', 1)
                ->first();

            if (! $smsTemplate) {
                // 如果没有对应的SMS模板，使用通用的 notification 模板
                $smsTemplateCode = 'notification';

                // 获取通知模板的SMS内容并替换变量
                $content = $template->getContent('sms');
                if (! $content) {
                    return;
                }
                $content = $this->replaceVariables($content, $data);

                // 使用 notification 通用模板，需要 title 和 content 参数
                $smsTemplateData = [
                    'title' => $template->name ?? '系统通知',
                    'content' => $content,
                ];
            } else {
                // 使用对应的SMS模板，直接传递通知模板的数据
                $smsTemplateData = $data;
            }
        }

        // 创建SMS通知（不指定driver，由SMS模块自行决定使用哪个启用的配置）
        $notification = new class($smsTemplateCode, $smsTemplateData) extends Notification
        {
            protected $templateCode;

            protected $data;

            public function __construct(string $templateCode, array $data)
            {
                $this->templateCode = $templateCode;
                $this->data = $data;
            }

            public function via($notifiable)
            {
                return ['sms'];
            }

            public function toSms($notifiable)
            {
                return [
                    'template' => $this->templateCode,
                    'data' => $this->data,
                    // 不指定driver，由SMS模块自行决定
                ];
            }
        };

        // 直接调用短信通道发送
        // SmsChannel 会调用 SmsServiceImpl::send()，由 SMS 模块自行处理所有细节
        NotificationFacade::send($notifiable, $notification);
    }

    /**
     * 替换模板变量.
     */
    protected function replaceVariables(string $content, array $data): string
    {
        // 自动添加应用名称变量（如果模板中包含 {app_name}）
        if (strpos($content, '{app_name}') !== false) {
            $data['app_name'] = config('app.name', 'YFSNS');
        }

        // 替换所有变量
        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }

        // 替换旧的硬编码的应用名称（兼容旧模板）
        $appName = config('app.name', 'YFSNS');
        $content = str_replace('Laravel 团队', $appName . ' 团队', $content);
        $content = str_replace('系统 团队', $appName . ' 团队', $content);

        return $content;
    }
}
