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

use App\Modules\System\Models\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 内容审核配置服务
 *
 * 专门管理内容审核相关的配置项，与系统配置分离
 * 支持模块化的审核开关管理，独立缓存策略
 */
class ContentReviewConfigService
{
    /**
     * 缓存键前缀
     */
    public const CACHE_PREFIX = 'content_review:';

    /**
     * 缓存时间（分钟）
     */
    public const CACHE_MINUTES = 60;

    /**
     * 支持的审核模块映射
     */
    public const MODULE_MAPPINGS = [
        'post' => 'content_post',
        'article' => 'content_article',
        'forumThread' => 'content_forum_thread',
        'comment' => 'content_comment',
        'topic' => 'content_topic',
        'avatar' => 'user_avatar',
    ];

    /**
     * 获取所有审核设置
     *
     * 返回所有内容审核相关配置的统一视图（向后兼容）
     */
    public function getAllSettings(): array
    {
        $settings = [];
        foreach (self::MODULE_MAPPINGS as $moduleName => $configKey) {
            $settings[$moduleName] = $this->isEnabled($moduleName);
        }

        return $settings;
    }

    /**
     * 更新所有审核设置
     *
     * 支持批量更新所有内容审核设置（向后兼容）
     */
    public function setAllSettings(array $settings): bool
    {
        $success = true;
        foreach ($settings as $moduleName => $enabled) {
            $result = $this->setEnabled($moduleName, (bool) $enabled);
            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * 检查指定模块是否开启审核
     */
    public function isEnabled(string $module): bool
    {
        $configKey = $this->mapModuleToConfigKey($module);
        $cacheKey = self::CACHE_PREFIX . $module;

        return Cache::remember($cacheKey, self::CACHE_MINUTES, function () use ($configKey) {
            $config = Config::where('key', $configKey)
                          ->where('group', 'content')
                          ->first();

            return $config ? (bool) $config->value : false;
        });
    }

    /**
     * 设置指定模块的审核开关
     */
    public function setEnabled(string $module, bool $enabled): bool
    {
        $configKey = $this->mapModuleToConfigKey($module);

        try {
            $config = Config::updateOrCreate(
                ['key' => $configKey, 'group' => 'content'],
                [
                    'value' => $enabled,
                    'type' => 'boolean',
                    'description' => "内容审核开关：{$module}模块",
                    'is_system' => true,
                ]
            );

            // 清除该模块的缓存
            $cacheKey = self::CACHE_PREFIX . $module;
            Cache::forget($cacheKey);

            Log::info("内容审核设置已更新", [
                'module' => $module,
                'config_key' => $configKey,
                'enabled' => $enabled,
                'cache_cleared' => $cacheKey
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("内容审核设置更新失败", [
                'module' => $module,
                'enabled' => $enabled,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 获取指定模块的配置详情
     */
    public function getModuleConfig(string $module): ?array
    {
        $configKey = $this->mapModuleToConfigKey($module);

        $config = Config::where('key', $configKey)
                      ->where('group', 'content')
                      ->first();

        if (!$config) {
            return null;
        }

        return [
            'key' => $config->key,
            'value' => (bool) $config->value,
            'type' => $config->type,
            'description' => $config->description,
            'is_system' => $config->is_system,
            'updated_at' => $config->updated_at,
        ];
    }

    /**
     * 获取所有模块的配置详情
     */
    public function getAllModuleConfigs(): array
    {
        $configs = [];
        foreach (self::MODULE_MAPPINGS as $moduleName => $configKey) {
            $config = $this->getModuleConfig($moduleName);
            if ($config) {
                $configs[$moduleName] = $config;
            }
        }

        return $configs;
    }

    /**
     * 清除所有内容审核缓存
     */
    public function clearAllCache(): void
    {
        // 清除所有模块的缓存
        foreach (array_keys(self::MODULE_MAPPINGS) as $module) {
            $cacheKey = self::CACHE_PREFIX . $module;
            Cache::forget($cacheKey);
        }

        // 清除分组缓存（如果有的话）
        Cache::forget('config_group:content');

        Log::info('所有内容审核缓存已清除', [
            'modules' => array_keys(self::MODULE_MAPPINGS)
        ]);
    }

    /**
     * 清除指定模块的缓存
     */
    public function clearModuleCache(string $module): void
    {
        $cacheKey = self::CACHE_PREFIX . $module;
        Cache::forget($cacheKey);

        Log::info('内容审核模块缓存已清除', [
            'module' => $module,
            'cache_key' => $cacheKey
        ]);
    }

    /**
     * 获取支持的模块列表
     */
    public function getSupportedModules(): array
    {
        return array_keys(self::MODULE_MAPPINGS);
    }

    /**
     * 获取模块配置键映射
     */
    public function getModuleMappings(): array
    {
        return self::MODULE_MAPPINGS;
    }

    /**
     * 映射模块名到配置键
     */
    protected function mapModuleToConfigKey(string $module): string
    {
        return self::MODULE_MAPPINGS[$module] ?? "content_{$module}";
    }

    /**
     * 批量操作：启用多个模块
     */
    public function enableModules(array $modules): array
    {
        $results = [];
        foreach ($modules as $module) {
            $results[$module] = $this->setEnabled($module, true);
        }

        return $results;
    }

    /**
     * 批量操作：禁用多个模块
     */
    public function disableModules(array $modules): array
    {
        $results = [];
        foreach ($modules as $module) {
            $results[$module] = $this->setEnabled($module, false);
        }

        return $results;
    }

    /**
     * 获取统计信息
     */
    public function getStatistics(): array
    {
        $enabledCount = 0;
        $totalCount = count(self::MODULE_MAPPINGS);

        foreach (array_keys(self::MODULE_MAPPINGS) as $module) {
            if ($this->isEnabled($module)) {
                $enabledCount++;
            }
        }

        return [
            'total_modules' => $totalCount,
            'enabled_modules' => $enabledCount,
            'disabled_modules' => $totalCount - $enabledCount,
            'enabled_percentage' => $totalCount > 0 ? round(($enabledCount / $totalCount) * 100, 2) : 0,
            'modules' => array_keys(self::MODULE_MAPPINGS),
        ];
    }

    /**
     * 重置所有模块为默认状态（全部关闭）
     */
    public function resetToDefaults(): bool
    {
        $results = [];
        foreach (array_keys(self::MODULE_MAPPINGS) as $module) {
            $results[$module] = $this->setEnabled($module, false);
        }

        $success = !in_array(false, $results);

        if ($success) {
            Log::info('内容审核配置已重置为默认状态（全部关闭）');
        }

        return $success;
    }

    /**
     * 导出配置（用于备份）
     */
    public function exportSettings(): array
    {
        $settings = $this->getAllModuleConfigs();

        return [
            'exported_at' => now()->toISOString(),
            'version' => '1.0',
            'settings' => $settings,
            'mappings' => self::MODULE_MAPPINGS,
        ];
    }

    /**
     * 导入配置（用于恢复）
     */
    public function importSettings(array $importData): bool
    {
        if (!isset($importData['settings']) || !is_array($importData['settings'])) {
            Log::error('导入配置失败：无效的数据格式');
            return false;
        }

        $success = true;
        foreach ($importData['settings'] as $module => $config) {
            if (isset($config['value'])) {
                $result = $this->setEnabled($module, (bool) $config['value']);
                if (!$result) {
                    $success = false;
                }
            }
        }

        if ($success) {
            Log::info('内容审核配置导入成功', [
                'imported_at' => now()->toISOString(),
                'modules_count' => count($importData['settings'])
            ]);
        }

        return $success;
    }
}
