<?php

namespace App\Services;

use App\Repositories\ConfigRepository;

/**
 * 配置解析器 - 声明式配置系统
 *
 * 负责将声明式配置转换为实际配置值
 */
class ConfigResolver
{
    protected ConfigRepository $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * 解析声明式配置
     *
     * @param string $group 配置组名
     * @param array $schema 声明式配置架构
     * @param array $staticConfigs 静态配置
     * @return array
     */
    public function resolve(string $group, array $schema, array $staticConfigs = []): array
    {
        $dynamicConfigs = $this->configRepository->resolveFromSchema($group, $schema);

        return array_merge($staticConfigs, $dynamicConfigs);
    }

    /**
     * 获取配置值（支持点语法）
     *
     * @param string $key 配置键 (如: 'avatar.max_size')
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->configRepository->get($key, $default);
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
        return $this->configRepository->set($key, $value, $type, $group, $description);
    }
}
