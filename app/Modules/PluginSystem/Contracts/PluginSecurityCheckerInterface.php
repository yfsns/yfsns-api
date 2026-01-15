<?php

namespace App\Modules\PluginSystem\Contracts;

/**
 * 插件安全检查接口
 *
 * 定义插件安全检查的标准契约
 */
interface PluginSecurityCheckerInterface
{
    /**
     * 执行完整的插件安全检查
     *
     * @param string $pluginName 插件名称
     * @param string $pluginPath 插件路径
     * @return array 检查结果 ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public function performSecurityCheck(string $pluginName, string $pluginPath): array;

    /**
     * 检查插件文件完整性
     *
     * @param string $pluginName 插件名称
     * @param string $pluginPath 插件路径
     * @return array 检查结果
     */
    public function checkFileIntegrity(string $pluginName, string $pluginPath): array;

    /**
     * 检查插件权限和访问控制
     *
     * @param string $pluginName 插件名称
     * @param array $pluginInfo 插件信息
     * @return array 检查结果
     */
    public function checkPermissions(string $pluginName, array $pluginInfo = []): array;

    /**
     * 检查插件依赖关系安全
     *
     * @param string $pluginName 插件名称
     * @param array $dependencies 依赖列表
     * @return array 检查结果
     */
    public function checkDependencies(string $pluginName, array $dependencies = []): array;

    /**
     * 检查插件代码安全（静态分析）
     *
     * @param string $pluginName 插件名称
     * @param string $pluginPath 插件路径
     * @return array 检查结果
     */
    public function checkCodeSecurity(string $pluginName, string $pluginPath): array;

    /**
     * 检查插件配置安全
     *
     * @param string $pluginName 插件名称
     * @param array $config 配置数据
     * @return array 检查结果
     */
    public function checkConfiguration(string $pluginName, array $config = []): array;

    /**
     * 检查插件数据库操作安全
     *
     * @param string $pluginName 插件名称
     * @param array $migrations 迁移文件列表
     * @return array 检查结果
     */
    public function checkDatabaseOperations(string $pluginName, array $migrations = []): array;

    /**
     * 获取安全检查策略配置
     *
     * @return array 策略配置
     */
    public function getSecurityPolicies(): array;
}
