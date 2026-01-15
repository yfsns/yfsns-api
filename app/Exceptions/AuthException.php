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

namespace App\Exceptions;

class AuthException extends BaseException
{
    /**
     * 创建认证失败异常.
     */
    public static function invalidCredentials(): self
    {
        return static::make('用户名或密码错误', 'INVALID_CREDENTIALS', 401);
    }

    /**
     * 创建账号禁用异常.
     */
    public static function userDisabled(): self
    {
        return static::make('账号已被禁用', 'USER_DISABLED', 403);
    }

    /**
     * 创建用户不存在异常.
     */
    public static function userNotFound(): self
    {
        return static::make('用户不存在', 'USER_NOT_FOUND', 404);
    }

    /**
     * 创建未认证异常.
     */
    public static function unauthenticated(): self
    {
        return static::make('请先登录', 'UNAUTHENTICATED', 401);
    }

    /**
     * 创建无权限异常.
     */
    public static function unauthorized(): self
    {
        return static::make('无权限访问', 'UNAUTHORIZED', 403);
    }

    /**
     * 创建验证码过期异常.
     */
    public static function verificationCodeExpired(): self
    {
        return static::make('验证码已过期', 'VERIFICATION_CODE_EXPIRED', 400);
    }

    /**
     * 创建验证码错误异常.
     */
    public static function invalidVerificationCode(): self
    {
        return static::make('验证码错误', 'INVALID_VERIFICATION_CODE', 400);
    }

    /**
     * 创建验证码尝试次数过多异常.
     */
    public static function tooManyVerificationAttempts(): self
    {
        return static::make('验证码尝试次数过多，请重新获取', 'TOO_MANY_VERIFICATION_ATTEMPTS', 429);
    }
}
