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

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheClearService
{
    /**
     * 清除单个缓存.
     */
    public function clear(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * 清除多个缓存.
     */
    public function clearMany(array $keys): void
    {
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * 清除网站配置相关缓存.
     */
    public function clearWebsiteConfig(): void
    {
        $this->clearMany([
            'website_config',
            'website_info_response',
            'storage_site_url',
        ]);
    }

    /**
     * 清除系统配置相关缓存.
     */
    public function clearSystemConfig(): void
    {
        $this->clearMany([
            'system_config',
            'system_config_response',
        ]);
    }

    /**
     * 清除内容审核配置相关缓存.
     */
    public function clearContentReviewConfig(): void
    {
        $this->clearMany([
            'content_review_config',
            'config_group:content',
        ]);
    }

    /**
     * 清除服务配置相关缓存.
     */
    public function clearServiceConfig(string $group): void
    {
        $this->clearMany([
            "service_config.{$group}.default",
            "service_config.{$group}.all",
        ]);
    }

    /**
     * 清除API配置相关缓存.
     */
    public function clearApiConfig(string $key): void
    {
        $this->clear("api_config:{$key}");
    }

    /**
     * 一键清除全部缓存.
     * 
     * 包括：
     * - 应用缓存（Cache::flush()）
     * - 配置缓存（config:clear）
     * - 路由缓存（route:clear）
     * - 视图缓存（view:clear）
     * - 其他模块缓存（敏感词、插件等）
     */
    public function clearAll(): array
    {
        $cleared = [];

        try {
            // 1. 清除应用缓存
            Cache::flush();
            $cleared[] = '应用缓存';

            // 2. 清除配置缓存
            try {
                Artisan::call('config:clear');
                $cleared[] = '配置缓存';
            } catch (\Exception $e) {
                // 忽略错误，继续执行
            }

            // 3. 清除路由缓存
            try {
                Artisan::call('route:clear');
                $cleared[] = '路由缓存';
            } catch (\Exception $e) {
                // 忽略错误，继续执行
            }

            // 4. 清除视图缓存
            try {
                Artisan::call('view:clear');
                $cleared[] = '视图缓存';
            } catch (\Exception $e) {
                // 忽略错误，继续执行
            }

            // 5. 清除敏感词缓存
            try {
                $sensitiveWordService = app(\App\Modules\SensitiveWord\Services\SensitiveWordService::class);
                if (method_exists($sensitiveWordService, 'clearCache')) {
                    $sensitiveWordService->clearCache();
                    $cleared[] = '敏感词缓存';
                }
            } catch (\Exception $e) {
                // 忽略错误，继续执行
            }

            // 6. 清除网站配置缓存
            $this->clearWebsiteConfig();
            $cleared[] = '网站配置缓存';

            // 7. 清除系统配置缓存
            $this->clearSystemConfig();
            $cleared[] = '系统配置缓存';

            // 8. 清除内容审核配置缓存
            $this->clearContentReviewConfig();
            $cleared[] = '内容审核配置缓存';

        } catch (\Exception $e) {
            // 记录错误但不中断
            Log::warning('清除缓存时发生错误', ['error' => $e->getMessage()]);
        }

        return [
            'cleared' => $cleared,
            'count' => count($cleared),
        ];
    }
}
