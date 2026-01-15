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

use App\Modules\Sms\Infrastructure\Services\SmsServiceImpl;
use App\Modules\Notification\Models\NotificationTemplate;
use Exception;

use function get_class;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    /**
     * @var SmsServiceImpl
     */
    protected $smsService;

    /**
     * 构造函数.
     */
    public function __construct()
    {
        $this->smsService = app(SmsServiceImpl::class);
    }

    /**
     * 发送通知.
     */
    public function send($notifiable, Notification $notification): void
    {
        Log::info('SmsChannel: 开始处理SMS通知', [
            'notification_class' => get_class($notification),
            'notifiable_id' => $notifiable->id,
            'user_phone' => $notifiable->phone ?? 'no phone'
        ]);

        if (! method_exists($notification, 'toSms')) {
            Log::warning('SmsChannel: 通知类缺少toSms方法');
            return;
        }

        $message = $notification->toSms($notifiable);

        if (empty($message)) {
            Log::warning('SmsChannel: toSms返回空消息');
            return;
        }

        $phone = $notifiable->phone ?? $notifiable->mobile ?? null;
        if (! $phone) {
            Log::warning('SMS通知发送失败：用户没有手机号', [
                'notifiable_id' => $notifiable->id ?? null,
                'notifiable_type' => get_class($notifiable),
            ]);

            return;
        }

        $templateCode = $message['template'] ?? '';
        $templateData = $message['data'] ?? [];

        Log::info('SmsChannel: 准备发送SMS', [
            'phone' => $phone,
            'template_code' => $templateCode,
            'template_data' => $templateData
        ]);

        try {
            // 从NotificationTemplate获取sms_template_id（统一使用通知模块的模板）
            $notificationTemplate = NotificationTemplate::where('code', $templateCode)
                ->where('status', true)
                ->first();

            if (! $notificationTemplate) {
                Log::warning('SmsChannel: NotificationTemplate不存在', [
                    'template_code' => $templateCode
                ]);
                return;
            }

            if (! $notificationTemplate->sms_template_id) {
                Log::warning('SmsChannel: NotificationTemplate未配置sms_template_id', [
                    'template_code' => $templateCode
                ]);
                return;
            }

            // 根据模板要求和短信服务商限制格式化数据
            $templateData = $this->formatTemplateDataForSms($notificationTemplate, $templateData);
            
            // 传递模板ID、变量顺序和模板数据给短信服务
            $variables = $notificationTemplate->variables ?? [];
            $result = $this->smsService->sendWithTemplateId(
                $phone, 
                $notificationTemplate->sms_template_id, 
                $templateData,
                null, // driver
                $variables // 变量顺序
            );
            
            Log::info('SmsChannel: 使用NotificationTemplate中的sms_template_id', [
                'notification_template_code' => $templateCode,
                'sms_template_id' => $notificationTemplate->sms_template_id
            ]);

            Log::info('SmsChannel: SMS发送结果', [
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? 'no response'
            ]);

            if (! $result['success']) {
                Log::error('SMS通知发送失败', [
                    'phone' => $phone,
                    'template' => $templateCode,
                    'error' => $result['message'] ?? '未知错误',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SmsChannel: SMS发送异常', [
                'phone' => $phone,
                'template_code' => $templateCode,
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * 根据模板要求和短信服务商限制格式化数据
     * 非验证码短信每个变量最多6个字符
     * 
     * @param NotificationTemplate $template 通知模板
     * @param array $data 原始数据
     * @return array 格式化后的数据
     */
    protected function formatTemplateDataForSms(NotificationTemplate $template, array $data): array
    {
        $formatted = [];
        
        foreach ($data as $key => $value) {
            // 处理时间格式
            if ($key === 'login_time') {
                if ($value instanceof \DateTime || $value instanceof \Carbon\Carbon) {
                    $value = $value->format('m-d'); // 格式：01-04，5个字符
                } elseif (is_string($value)) {
                    // 如果已经是字符串，尝试解析并格式化
                    try {
                        $date = new \DateTime($value);
                        $value = $date->format('m-d');
                    } catch (\Exception $e) {
                        // 如果解析失败，保持原值
                    }
                }
            }
            
            // 限制所有变量长度，非验证码短信每个变量最多6个字符
            $value = (string) $value;
            if (mb_strlen($value) > 6) {
                $value = mb_substr($value, 0, 6);
            }
            
            $formatted[$key] = $value;
        }
        
        return $formatted;
    }

}
