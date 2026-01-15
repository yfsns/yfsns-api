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

namespace App\Modules\Notification\Models;

use Illuminate\Database\Eloquent\Model;

use function in_array;
use function is_array;
use function is_string;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'category',
        'channels',
        'content',
        'variables',
        'sms_template_id',  // 新增：SMS模板ID
        'status',
        'priority',
        'remark',
    ];

    protected $casts = [
        'channels' => 'array',
        'content' => 'array',
        'variables' => 'array',
        'status' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * 获取模板内容.
     *
     * @param string $channel 通道
     */
    public function getContent(string $channel): ?string
    {
        return is_array($this->content) ? ($this->content[$channel] ?? null) : $this->content;
    }

    /**
     * 获取SMS通道的模板内容.
     *
     * @param string $smsDriver SMS驱动 (aliyun, tencent)
     *
     * @return null|array 返回包含template_id和content的数组
     */
    public function getSmsContent(string $smsDriver): ?array
    {
        if (! is_array($this->content) || ! isset($this->content['sms'])) {
            return null;
        }

        $smsContent = $this->content['sms'];

        // 如果sms是字符串，说明是旧格式，直接返回
        if (is_string($smsContent)) {
            return [
                'template_id' => null,
                'content' => $smsContent,
            ];
        }

        // 如果sms是数组，查找指定驱动的内容
        if (is_array($smsContent) && isset($smsContent[$smsDriver])) {
            return $smsContent[$smsDriver];
        }

        return null;
    }

    /**
     * 设置SMS通道的模板内容.
     *
     * @param string $smsDriver  SMS驱动
     * @param string $templateId 模板ID
     * @param string $content    模板内容
     */
    public function setSmsContent(string $smsDriver, string $templateId, string $content): void
    {
        $contentArray = is_array($this->content) ? $this->content : [];

        if (! isset($contentArray['sms'])) {
            $contentArray['sms'] = [];
        }

        $contentArray['sms'][$smsDriver] = [
            'template_id' => $templateId,
            'content' => $content,
        ];

        $this->content = $contentArray;
    }

    /**
     * 检查模板是否启用.
     */
    public function isEnabled(): bool
    {
        return $this->status;
    }

    /**
     * 获取优先级文本.
     */
    public function getPriorityTextAttribute(): string
    {
        return match ($this->priority) {
            1 => '低',
            2 => '中',
            3 => '高',
            default => '未知',
        };
    }

    /**
     * 获取分类文本.
     */
    public function getCategoryTextAttribute(): string
    {
        return match ($this->category) {
            'general' => '通用',
            'user' => '用户',
            'order' => '订单',
            'security' => '安全',
            default => '未知',
        };
    }

    /**
     * 检查是否为系统通知.
     */
    public function isSystemNotification(): bool
    {
        return $this->type === 'system';
    }

    /**
     * 检查是否为邮件通知.
     */
    public function isEmailNotification(): bool
    {
        return $this->type === 'email';
    }

    /**
     * 检查是否为短信通知.
     */
    public function isSmsNotification(): bool
    {
        return $this->type === 'sms';
    }

    /**
     * 获取支持的渠道列表.
     */
    public function getSupportedChannels(): array
    {
        return $this->channels ?? [];
    }

    /**
     * 检查是否支持指定渠道.
     */
    public function supportsChannel(string $channel): bool
    {
        return in_array($channel, $this->getSupportedChannels());
    }
}
