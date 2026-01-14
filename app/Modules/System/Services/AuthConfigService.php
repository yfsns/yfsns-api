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

/**
 * 认证配置服务
 *
 * 管理注册方式、登录方式和密码强度等认证相关配置
 */
class AuthConfigService
{
    /**
     * 注册方式常量
     */
    public const REGISTRATION_METHOD_USERNAME = 'username';
    public const REGISTRATION_METHOD_EMAIL = 'email';
    public const REGISTRATION_METHOD_SMS = 'sms';

    /**
     * 登录方式常量
     */
    public const LOGIN_METHOD_USERNAME = 'username';
    public const LOGIN_METHOD_EMAIL = 'email';
    public const LOGIN_METHOD_SMS = 'sms';

    /**
     * 密码强度常量
     */
    public const PASSWORD_STRENGTH_WEAK = 'weak';
    public const PASSWORD_STRENGTH_MEDIUM = 'medium';
    public const PASSWORD_STRENGTH_STRONG = 'strong';

    /**
     * 获取支持的注册方式列表
     */
    public function getSupportedRegistrationMethods(): array
    {
        return [
            self::REGISTRATION_METHOD_USERNAME => '用户名注册',
            self::REGISTRATION_METHOD_EMAIL => '邮箱注册',
            self::REGISTRATION_METHOD_SMS => '短信注册',
        ];
    }

    /**
     * 获取支持的登录方式列表
     */
    public function getSupportedLoginMethods(): array
    {
        return [
            self::LOGIN_METHOD_USERNAME => '用户名登录',
            self::LOGIN_METHOD_EMAIL => '邮箱验证码登录',
            self::LOGIN_METHOD_SMS => '短信验证码登录',
        ];
    }

    /**
     * 获取密码强度选项列表
     */
    public function getPasswordStrengthOptions(): array
    {
        return [
            self::PASSWORD_STRENGTH_WEAK => '弱密码（长度≥6）',
            self::PASSWORD_STRENGTH_MEDIUM => '中等密码（字母+数字，长度≥6）',
            self::PASSWORD_STRENGTH_STRONG => '强密码（大小写+数字+符号，长度≥8）',
        ];
    }

    /**
     * 获取启用的注册方式
     */
    public function getEnabledRegistrationMethods(): array
    {
        $configService = app(ConfigService::class);
        $methods = $configService->get('registration_methods', 'auth');
        if (empty($methods)) {
            return []; // 没有启用任何注册方式
        }

        if (is_string($methods)) {
            return explode(',', $methods);
        }

        return is_array($methods) ? $methods : [];
    }

    /**
     * 获取启用的登录方式
     */
    public function getEnabledLoginMethods(): array
    {
        $configService = app(ConfigService::class);
        $methods = $configService->get('login_methods', 'auth');
        if (empty($methods)) {
            return []; // 没有启用任何登录方式
        }

        if (is_string($methods)) {
            return explode(',', $methods);
        }

        return is_array($methods) ? $methods : [];
    }

    /**
     * 获取密码强度要求
     */
    public function getPasswordStrength(): string
    {
        $configService = app(ConfigService::class);
        return $configService->get('password_strength', 'auth') ?? self::PASSWORD_STRENGTH_MEDIUM;
    }


    /**
     * 检查注册方式是否启用
     */
    public function isRegistrationMethodEnabled(string $method): bool
    {
        $enabledMethods = $this->getEnabledRegistrationMethods();
        return in_array($method, $enabledMethods);
    }

    /**
     * 检查登录方式是否启用
     */
    public function isLoginMethodEnabled(string $method): bool
    {
        $enabledMethods = $this->getEnabledLoginMethods();
        return in_array($method, $enabledMethods);
    }

    /**
     * 获取认证配置摘要
     */
    public function getAuthConfigSummary(): array
    {
        $configService = app(ConfigService::class);
        return [
            'registration_methods' => $this->getEnabledRegistrationMethods(),
            'login_methods' => $this->getEnabledLoginMethods(),
            'password_strength' => $this->getPasswordStrength(),
            'login_attempts_limit' => $configService->get('login_attempts_limit', 'auth', 5),
            'login_lockout_duration' => $configService->get('login_lockout_duration', 'auth', 900),
        ];
    }

    /**
     * 验证注册方式参数
     */
    public function validateRegistrationMethod(string $method): bool
    {
        return array_key_exists($method, $this->getSupportedRegistrationMethods()) &&
               $this->isRegistrationMethodEnabled($method);
    }

    /**
     * 验证登录方式参数
     */
    public function validateLoginMethod(string $method): bool
    {
        return array_key_exists($method, $this->getSupportedLoginMethods()) &&
               $this->isLoginMethodEnabled($method);
    }
}
