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

namespace App\Modules\Sms\Drivers;

interface SmsDriverInterface
{
    /**
     * 获取驱动名称.
     */
    public function getName(): string;

    /**
     * 获取驱动配置.
     */
    public function getConfig(): array;

    /**
     * 发送短信
     *
     * @param string $phone        手机号
     * @param string $templateCode 模板代码
     * @param array  $templateData 模板数据
     */
    public function send(string $phone, string $templateCode, array $templateData = []): array;
}
