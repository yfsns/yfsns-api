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

namespace App\Modules\Notification\Services;

use App\Modules\Notification\Models\EmailConfig;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * 发送邮件.
     *
     * @param string           $to      收件人邮箱
     * @param string           $subject 邮件主题
     * @param string           $content 邮件内容
     * @param null|EmailConfig $config  邮件配置
     */
    public function send(string $to, string $subject, string $content, ?EmailConfig $config = null): bool
    {
        // 如果没有指定配置，使用系统配置
        if (! $config) {
            $config = EmailConfig::getConfig();
        }

        // 如果数据库中没有配置，使用Laravel默认配置
        if (! $config) {
            Mail::raw($content, function ($message) use ($to, $subject): void {
                $message->to($to)
                    ->subject($subject);
                // 设置默认发件人（如果Laravel配置中有的话）
                $fromAddress = config('mail.from.address');
                $fromName = config('mail.from.name');
                if ($fromAddress && is_string($fromAddress)) {
                    $message->from($fromAddress, $fromName ?: null);
                }
            });
        } else {
            // 使用数据库中的配置
            $this->sendWithConfig($to, $subject, $content, $config);
        }

        return true;
    }

    /**
     * 发送验证码
     *
     * @param string $email  邮箱地址
     * @param string $code   验证码
     * @param int    $expire 过期时间(分钟)
     */
    public function sendVerificationCode(string $email, string $code, int $expire = 5): bool
    {
        // 存储验证码
        $key = "email_verification_code:{$email}";
        Cache::put($key, $code, $expire * 60);

        // 添加日志
        Log::info('验证码已存储', [
            'email' => $email,
            'code' => $code,
            'key' => $key,
            'expire' => $expire,
        ]);

        // 发送验证码邮件
        $content = "您的验证码是：{$code}，{$expire}分钟内有效。";

        return $this->send($email, '验证码', $content);
    }

    /**
     * 验证验证码
     *
     * @param string $email 邮箱地址
     * @param string $code  验证码
     */
    public function verifyCode(string $email, string $code): bool
    {
        $key = "email_verification_code:{$email}";
        $cachedCode = Cache::get($key);

        // 添加日志
        Log::info('验证码校验', [
            'email' => $email,
            'input_code' => $code,
            'cached_code' => $cachedCode,
            'key' => $key,
        ]);

        if (! $cachedCode || $cachedCode !== $code) {
            return false;
        }

        // 验证成功后删除验证码
        Cache::forget($key);

        return true;
    }

    /**
     * 发送欢迎邮件.
     *
     * @param string $email 邮箱地址
     * @param string $name  用户名
     */
    public function sendWelcomeEmail(string $email, string $name): bool
    {
        $content = "亲爱的{$name}：\n\n欢迎加入我们！\n\n感谢您的注册。";

        return $this->send($email, '欢迎加入我们', $content);
    }

    /**
     * 发送密码重置邮件.
     *
     * @param string $email 邮箱地址
     * @param string $token 重置令牌
     */
    public function sendPasswordResetEmail(string $email, string $token): bool
    {
        $content = "您正在进行密码重置操作，请点击以下链接完成重置：\n\n{$token}\n\n如果这不是您的操作，请忽略此邮件。";

        return $this->send($email, '密码重置', $content);
    }

    /**
     * 使用指定配置发送邮件.
     */
    protected function sendWithConfig(string $to, string $subject, string $content, EmailConfig $config): void
    {
        // 临时配置邮件服务
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $config->host,
            'mail.mailers.smtp.port' => $config->port,
            'mail.mailers.smtp.encryption' => $config->encryption,
            'mail.mailers.smtp.username' => $config->username,
            'mail.mailers.smtp.password' => $config->password,
        ]);

        // 使用显式的from地址发送邮件
        Mail::raw($content, function ($message) use ($to, $subject, $config): void {
            $message->from($config->from_address, $config->from_name)
                ->to($to)
                ->subject($subject);
        });
    }
}
