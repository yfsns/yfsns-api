<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 配置仓库 - 声明式配置系统
 *
 * 自动处理数据源（数据库/缓存/默认值）
 */
class ConfigRepository
{
    /**
     * 配置缓存时间（秒）
     */
    const CACHE_TTL = 3600; // 1小时

    /**
     * 配置组缓存键前缀
     */
    const CACHE_GROUP_PREFIX = 'config_group:';

    /**
     * 单个配置缓存键前缀
     */
    const CACHE_KEY_PREFIX = 'config_key:';

    /**
     * 获取配置值
     *
     * @param string $key 配置键 (如: 'avatar.max_size')
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Cache::remember(
            self::CACHE_KEY_PREFIX . $key,
            self::CACHE_TTL,
            fn() => $this->getFromDatabase($key, $default)
        );
    }

    /**
     * 获取配置组
     *
     * @param string $group 配置组名 (如: 'avatar')
     * @return array
     */
    public function getGroup(string $group): array
    {
        $configs = Cache::remember(
            self::CACHE_GROUP_PREFIX . $group,
            self::CACHE_TTL,
            fn() => $this->getGroupFromDatabase($group)
        );

        return $this->resolveConfigs($group, $configs);
    }

    /**
     * 设置配置值
     *
     * @param string $key 配置键
     * @param mixed $value 配置值
     * @param string $type 值类型
     * @param string $group 配置组
     * @param string $description 描述
     * @return bool
     */
    public function set(string $key, $value, string $type = 'string', string $group = 'system', string $description = ''): bool
    {
        $result = DB::table('system_configs')->updateOrInsert(
            ['key' => $key],
            [
                'value' => (string) $value,
                'type' => $type,
                'group' => $group,
                'description' => $description,
                'updated_at' => now()
            ]
        );

        if ($result) {
            $this->clearCache($key, $group);
        }

        return $result;
    }

    /**
     * 批量设置配置
     *
     * @param array $configs 配置数组
     * @param string $group 配置组
     * @return bool
     */
    public function setGroup(array $configs, string $group = 'system'): bool
    {
        DB::beginTransaction();

        try {
            foreach ($configs as $key => $config) {
                $fullKey = $group . '.' . $key;
                $this->set(
                    $fullKey,
                    $config['value'] ?? $config['default'] ?? null,
                    $config['type'] ?? 'string',
                    $group,
                    $config['description'] ?? ''
                );
            }

            DB::commit();
            $this->clearGroupCache($group);
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * 根据声明式配置自动解析
     *
     * @param string $group 配置组名
     * @param array $schema 声明式配置架构
     * @return array
     */
    public function resolveFromSchema(string $group, array $schema): array
    {
        $dbConfigs = $this->getGroup($group);
        $result = [];

        foreach ($schema as $key => $definition) {
            $fullKey = $group . '.' . $key;

            if (isset($dbConfigs[$fullKey])) {
                // 从数据库获取并转换类型
                $result[$key] = $this->castValue($dbConfigs[$fullKey], $definition['type'] ?? 'string');
            } else {
                // 使用默认值
                $result[$key] = $definition['default'] ?? null;
            }
        }

        return $result;
    }

    /**
     * 验证配置值
     *
     * @param string $key 配置键
     * @param mixed $value 配置值
     * @param array $definition 配置定义
     * @return bool
     */
    public function validateValue(string $key, $value, array $definition): bool
    {
        // 这里可以集成Laravel的Validator
        // 暂时返回true
        return true;
    }

    /**
     * 从数据库获取配置值
     */
    private function getFromDatabase(string $key, $default = null)
    {
        $config = DB::table('system_configs')
            ->where('key', $key)
            ->first();

        if (!$config) {
            return $default;
        }

        return $this->castValue($config->value, $config->type);
    }

    /**
     * 从数据库获取配置组
     */
    private function getGroupFromDatabase(string $group): array
    {
        return DB::table('system_configs')
            ->where('group', $group)
            ->pluck('value', 'key')
            ->map(function ($value, $key) {
                $config = DB::table('system_configs')->where('key', $key)->first();
                return [
                    'value' => $value,
                    'type' => $config->type ?? 'string'
                ];
            })
            ->toArray();
    }

    /**
     * 解析配置数组
     */
    private function resolveConfigs(string $group, array $configs): array
    {
        $result = [];

        foreach ($configs as $key => $configData) {
            // 对于视图，我们需要原始字符串值（用于表单输入）
            // 不要转换类型
            $result[$key] = $configData['value'];
        }

        return $result;
    }

    /**
     * 类型转换
     */
    private function castValue(string $value, string $type)
    {
        return match ($type) {
            'integer', 'int' => (int) $value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'float', 'double' => (float) $value,
            'json' => json_decode($value, true),
            'array' => json_decode($value, true) ?? [],
            default => $value,
        };
    }

    /**
     * 清除缓存
     */
    private function clearCache(string $key, string $group): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX . $key);
        $this->clearGroupCache($group);
    }

    /**
     * 清除组缓存
     */
    private function clearGroupCache(string $group): void
    {
        Cache::forget(self::CACHE_GROUP_PREFIX . $group);
    }

    /**
     * 获取所有配置组
     */
    public function getAllGroups(): array
    {
        return DB::table('system_configs')
            ->distinct()
            ->pluck('group')
            ->toArray();
    }

    /**
     * 导出配置
     */
    public function exportGroup(string $group): array
    {
        return DB::table('system_configs')
            ->where('group', $group)
            ->get()
            ->map(function ($config) {
                return [
                    'key' => $config->key,
                    'value' => $config->value,
                    'type' => $config->type,
                    'description' => $config->description,
                ];
            })
            ->toArray();
    }

    /**
     * 导入配置
     */
    public function importGroup(string $group, array $configs): bool
    {
        DB::beginTransaction();

        try {
            foreach ($configs as $config) {
                $this->set(
                    $config['key'],
                    $config['value'],
                    $config['type'] ?? 'string',
                    $group,
                    $config['description'] ?? ''
                );
            }

            DB::commit();
            $this->clearGroupCache($group);
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }
}
