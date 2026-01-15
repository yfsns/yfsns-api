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

namespace App\Modules\PluginSystem\Contracts;

interface PluginInterface
{
    /**
     * 获取插件信息.
     */
    public function getInfo();

    /**
     * 启用插件.
     */
    public function enable();

    /**
     * 禁用插件.
     */
    public function disable();

    /**
     * 安装插件.
     */
    public function install();

    /**
     * 卸载插件.
     */
    public function uninstall();

    /**
     * 更新插件.
     */
    public function update();
}
