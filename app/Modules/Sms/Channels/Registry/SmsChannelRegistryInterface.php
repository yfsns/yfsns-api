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

namespace App\Modules\Sms\Channels\Registry;

use App\Modules\Sms\Contracts\SmsChannelInterface;

interface SmsChannelRegistryInterface
{
    /**
     * 注册短信通道
     *
     * @param string $channelType 通道类型标识
     * @param string $driverClass 驱动类名
     */
    public function registerChannel(string $channelType, string $driverClass): void;

    /**
     * 获取短信通道实例
     *
     * @param string $channelType 通道类型标识
     * @return SmsChannelInterface|null
     */
    public function getChannel(string $channelType): ?SmsChannelInterface;

    /**
     * 检查通道是否存在
     *
     * @param string $channelType 通道类型标识
     * @return bool
     */
    public function hasChannel(string $channelType): bool;

    /**
     * 获取所有可用通道列表
     *
     * @return array [channelType => driverClass]
     */
    public function getAvailableChannels(): array;

    /**
     * 获取通道信息列表（包含元数据）
     *
     * @return array 通道信息数组
     */
    public function getChannelInfos(): array;

    /**
     * 注销通道
     *
     * @param string $channelType 通道类型标识
     */
    public function unregisterChannel(string $channelType): void;
}
