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

namespace App\Modules\System\Services;

use App\Modules\System\Models\WebsiteConfig;
use App\Modules\System\Services\CacheClearService;
use Illuminate\Support\Facades\Cache;

class WebsiteConfigService
{
    protected $cacheKey = 'website_config';

    public function __construct(
        protected CacheClearService $cacheClear
    ) {}

    /**
     * 获取网站配置（只保留一条记录）
     */
    public function getConfig()
    {
        return Cache::remember($this->cacheKey, 60, function () {
            return WebsiteConfig::firstOrCreate(['id' => 1], WebsiteConfig::getDefaultConfig());
        });
    }

    /**
     * 更新网站配置
     */
    public function update(array $data)
    {
        $config = WebsiteConfig::updateOrCreate(
            ['id' => 1],
            WebsiteConfig::filterFillableData($data)
        );

        $this->clearAllCache();

        return $config;
    }

    /**
     * 清除所有相关缓存.
     */
    protected function clearAllCache(): void
    {
        $this->cacheClear->clearWebsiteConfig();
    }

}
