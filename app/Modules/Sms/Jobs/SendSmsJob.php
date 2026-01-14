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

namespace App\Modules\Sms\Jobs;

use App\Modules\Sms\Infrastructure\Services\SmsServiceImpl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phone;

    protected $templateCode;

    protected $templateData;

    protected $driver;

    /**
     * 创建一个新的任务实例.
     */
    public function __construct(string $phone, string $templateCode, array $templateData = [], ?string $driver = null)
    {
        $this->phone = $phone;
        $this->templateCode = $templateCode;
        $this->templateData = $templateData;
        $this->driver = $driver;
    }

    /**
     * 执行任务
     */
    public function handle(SmsServiceImpl $smsService): void
    {
        $smsService->send($this->phone, $this->templateCode, $this->templateData, $this->driver);
    }
}
