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

use Exception;

/**
 * 第三方登录插件接口规范.
 *
 * 所有第三方登录插件必须实现此接口，以便系统统一管理和前端统一接入
 */
interface LoginPluginInterface extends PluginInterface
{
    /**
     * 获取插件唯一标识符.
     *
     * 格式建议：{author}-{plugin-name}-v{version}
     * 例如：yfsns-wechat-login-v1、other-qq-login-v1
     *
     * 注意：此ID必须全局唯一，用于区分不同作者开发的同名插件
     *
     * @return string 插件唯一标识符
     */
    public function getUniqueId(): string;

    /**
     * 获取插件支持的登录方式列表.
     *
     * @return array 返回登录方式数组
     *               [
     *               [
     *               'id' => 'qrcode',                    // 方法ID（插件内唯一）
     *               'name' => '扫码登录',                // 显示名称
     *               'type' => 'qrcode',                  // 登录类型：qrcode/oauth/sms/email
     *               'platform' => ['web', 'mobile'],     // 支持的平台：web/mobile/miniprogram
     *               'icon' => 'https://...',             // 图标URL（可选）
     *               'description' => '使用微信扫码登录', // 描述（可选）
     *               'config' => [                        // 额外配置（可选）
     *               'scope' => 'snsapi_login',
     *               ...
     *               ]
     *               ],
     *               ...
     *               ]
     */
    public function getLoginMethods(): array;

    /**
     * 获取授权URL.
     *
     * @param string $methodId    登录方法ID（从 getLoginMethods() 返回的 id）
     * @param string $redirectUri 回调地址（完整URL）
     * @param array  $params      额外参数
     *                            - state: 状态参数（可选）
     *                            - scope: 授权范围（可选，仅OAuth类型）
     *                            - 其他插件自定义参数
     *
     * @throws Exception 如果方法ID不存在或参数错误
     *
     * @return string 授权URL
     */
    public function getAuthUrl(string $methodId, string $redirectUri, array $params = []): string;

    /**
     * 处理登录回调.
     *
     * @param string $methodId     登录方法ID
     * @param array  $callbackData 回调数据
     *                             - code: 授权码（必需）
     *                             - state: 状态参数（可选）
     *                             - 其他第三方平台返回的参数
     *
     * @throws Exception 如果处理失败
     *
     * @return array 登录结果
     *               [
     *               'success' => true,              // 是否成功
     *               'token_data' => [                // JWT token数据（成功时必需）
     *               'access_token' => '...',
     *               'refresh_token' => '...',
     *               'token_type' => 'Bearer',
     *               'expires_in' => 3600,
     *               ...
     *               ],
     *               'user' => [                      // 用户信息（可选）
     *               'id' => 1,
     *               'nickname' => '...',
     *               ...
     *               ],
     *               'third_party_info' => [          // 第三方平台用户信息（可选）
     *               'openid' => '...',
     *               'nickname' => '...',
     *               ...
     *               ],
     *               'redirect' => '...',             // 前端重定向地址（可选）
     *               'error' => '...',                 // 错误信息（失败时）
     *               'error_code' => '...'             // 错误代码（失败时）
     *               ]
     */
    public function handleCallback(string $methodId, array $callbackData): array;

    /**
     * 检查插件配置是否完整.
     *
     * 用于验证插件是否已正确配置（如AppID、Secret等）
     *
     * @return bool 配置是否完整
     */
    public function isConfigured(): bool;

    /**
     * 获取插件配置说明.
     *
     * 用于在管理后台显示配置提示
     *
     * @return array 配置说明
     *               [
     *               'required' => [                  // 必需配置项
     *               [
     *               'key' => 'app_id',
     *               'name' => 'AppID',
     *               'description' => '微信开放平台AppID',
     *               'type' => 'string'
     *               ],
     *               ...
     *               ],
     *               'optional' => [                  // 可选配置项
     *               ...
     *               ]
     *               ]
     */
    public function getConfigSchema(): array;
}
