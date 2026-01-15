<?php

use App\Repositories\ConfigRepository;

/**
 * 配置助手函数 - 声明式配置系统

 */

if (!function_exists('config_repo')) {
    /**
     * 获取配置仓库实例
     */
    function config_repo(): ConfigRepository
    {
        return app(ConfigRepository::class);
    }
}

if (!function_exists('config_schema')) {
    /**
     * 根据声明式配置架构获取配置
     *
     * @param string $group 配置组名
     * @param array $schema 声明式配置架构
     * @return array
     */
    function config_schema(string $group, array $schema): array
    {
        return config_repo()->resolveFromSchema($group, $schema);
    }
}

if (!function_exists('config_dynamic')) {
    /**
     * 获取动态配置值
     *
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    function config_dynamic(string $key, $default = null)
    {
        return config_repo()->get($key, $default);
    }
}
