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

namespace App\Modules\PluginSystem;

use Exception;

/**
 * 声明式插件基类
 *
 * 通过属性声明来简化插件开发，提供更简洁的API
 */
abstract class BaseDeclarativePlugin extends BasePlugin
{

    /**
     * 获取插件信息
     *
     * 插件类可以通过重写此方法来自定义信息
     */
    public function getInfo(): array
    {
        return [
            'name' => $this->name ?: 'unknown',
            'version' => $this->version ?: '1.0.0',
            'description' => $this->description ?: '',
            'author' => $this->author ?: '',
            'icon' => '',
            'tags' => [],
            'dependencies' => [],
            'permissions' => [],
            'settings_schema' => [],
        ];
    }

    /**
     * 获取插件名称
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 获取插件版本
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * 获取插件描述
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * 获取插件作者
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * 获取插件图标
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * 获取插件标签
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * 获取插件依赖
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * 获取插件权限
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * 获取插件设置架构
     */
    public function getSettingsSchema(): array
    {
        return $this->settingsSchema;
    }

    /**
     * 安装插件
     *
     * 子类可以重写 onInstall 方法来自定义安装逻辑
     */
    public function install()
    {
        try {
            // 调用子类的安装逻辑
            if (method_exists($this, 'onInstall')) {
                $this->onInstall();
            }
        } catch (Exception $e) {
            throw new Exception('插件安装失败: ' . $e->getMessage());
        }
    }

    /**
     * 卸载插件
     *
     * 子类可以重写 onUninstall 方法来自定义卸载逻辑
     */
    public function uninstall()
    {
        try {
            // 调用子类的卸载逻辑
            if (method_exists($this, 'onUninstall')) {
                $this->onUninstall();
            }
        } catch (Exception $e) {
            throw new Exception('插件卸载失败: ' . $e->getMessage());
        }
    }

    /**
     * 启用插件
     *
     * 子类可以重写 onEnable 方法来自定义启用逻辑
     */
    public function enable(): void
    {
        try {
            // 调用子类的启用逻辑
            if (method_exists($this, 'onEnable')) {
                $this->onEnable();
            }
        } catch (Exception $e) {
            throw new Exception('插件启用失败: ' . $e->getMessage());
        }
    }

    /**
     * 禁用插件
     *
     * 子类可以重写 onDisable 方法来自定义禁用逻辑
     */
    public function disable(): void
    {
        try {
            // 调用子类的禁用逻辑
            if (method_exists($this, 'onDisable')) {
                $this->onDisable();
            }
        } catch (Exception $e) {
            throw new Exception('插件禁用失败: ' . $e->getMessage());
        }
    }
}
