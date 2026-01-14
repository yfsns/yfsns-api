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

namespace App\Modules\Sms\Contracts;

use App\Modules\PluginSystem\Contracts\PluginInterface;
use App\Modules\Sms\Channels\Registry\SmsChannelRegistryInterface;

interface SmsChannelPluginInterface extends PluginInterface
{
    /**
     * 注册短信通道
     *
     * @param SmsChannelRegistryInterface $registry 通道注册器
     */
    public function registerSmsChannels(SmsChannelRegistryInterface $registry): void;

    /**
     * 获取插件提供的通道列表
     *
     * @return array 通道配置数组，每个通道包含 type, name, driver_class, description 等信息
     */
    public function getSmsChannels(): array;
}
