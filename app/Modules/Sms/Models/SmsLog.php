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

namespace App\Modules\Sms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsLog extends Model
{
    use SoftDeletes;

    protected $table = 'sms_logs';

    protected $fillable = [
        'phone',
        'content',
        'template_id',
        'template_code',     // 模板代码
        'template_data',     // 发送的数据（JSON）
        'driver',           // 通道类型（aliyun/tencent等）
        'status',           // 发送状态（0=失败, 1=成功）
        'error_message',    // 错误消息
        'response_data',    // API响应数据（JSON）
        'ip',               // 发送请求的IP地址
        'user_agent',       // 用户代理信息
    ];

    protected $casts = [
        'template_data' => 'array',
        'response_data' => 'array',
        'status' => 'integer',
        'ip' => 'string',
        'user_agent' => 'string',
        'template_code' => 'string',
    ];

    /**
     * 关联模板
     */
    public function template()
    {
        return $this->belongsTo(SmsTemplate::class, 'template_id');
    }

    /**
     * 标记为发送成功
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 1,
        ]);
    }

    /**
     * 标记为发送失败.
     */
    public function markAsFailed($error): void
    {
        $this->update([
            'status' => 0,
            'error_message' => $error,
        ]);
    }

    /**
     * 从短信发送结果创建日志记录
     *
     * @param string $phone 手机号
     * @param SmsTemplate $template 短信模板
     * @param array $data 发送的数据
     * @param mixed $channelConfig 通道配置
     * @param array $result 发送结果
     * @return static
     */
    public static function createFromSmsSend(string $phone, SmsTemplate $template, array $data, $channelConfig, array $result): static
    {
        return static::create([
            'phone' => $phone,
            'template_id' => $template->id,
            'template_code' => $template->code,
            'template_data' => $data,
            'driver' => $channelConfig->driver,
            'status' => ($result['success'] ?? false) ? 1 : 0,
            'error_message' => $result['message'] ?? null,
            'response_data' => $result['data'] ?? null,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
