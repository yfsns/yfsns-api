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

namespace App\Modules\Auth\Services;

use App\Exceptions\AuthException;
use App\Modules\Sms\Services\SmsService;
use App\Modules\Sms\Infrastructure\Services\SmsServiceImpl;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 验证码服务类
 *
 * 支持邮箱验证码和短信验证码的发送和验证
 */
class VerificationService
{
    protected ?SmsService $smsService;
    protected ?SmsServiceImpl $smsServiceImpl;

    // 验证码配置
    protected array $config = [
        'email' => [
            'length' => 6,      // 验证码长度
            'ttl' => 300,       // 过期时间（秒）
            'max_attempts' => 5, // 最大验证次数
            'send_limit' => 60,  // 发送间隔限制（秒）
        ],
        'sms' => [
            'length' => 6,
            'ttl' => 300,
            'max_attempts' => 5,
            'send_limit' => 60,
        ],
    ];

    public function __construct(?SmsService $smsService = null, ?SmsServiceImpl $smsServiceImpl = null)
    {
        $this->smsService = $smsService;
        $this->smsServiceImpl = $smsServiceImpl ?? app(SmsServiceImpl::class);
    }

    /**
     * 发送邮箱验证码
     *
     * @param string $email 邮箱地址
     * @param string $type 验证码类型 (register, login, reset_password, etc.)
     * @return array 发送结果
     */
    public function sendEmailCode(string $email, string $type = 'register'): array
    {
        try {
            // 验证邮箱格式
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => '邮箱格式不正确',
                ];
            }

            // 检查发送频率限制
            if (!$this->checkSendLimit($email, 'email')) {
                return [
                    'success' => false,
                    'message' => '发送过于频繁，请稍后再试',
                ];
            }

            // 生成验证码
            $code = $this->generateCode($this->config['email']['length']);

            // 缓存验证码
            $this->cacheCode($email, $code, $type, 'email');

            // 发送邮件
            $this->sendEmail($email, $code, $type);

            // 记录发送限制
            $this->recordSendLimit($email, 'email');

            Log::info('邮箱验证码发送成功', [
                'email' => $email,
                'type' => $type,
                'code_length' => strlen($code),
            ]);

            return [
                'success' => true,
                'message' => '验证码发送成功',
                'data' => [
                    'email' => $email,
                    'ttl' => $this->config['email']['ttl'],
                ],
            ];

        } catch (\Exception $e) {
            Log::error('邮箱验证码发送失败', [
                'email' => $email,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '验证码发送失败，请稍后再试',
            ];
        }
    }

    /**
     * 发送短信验证码
     *
     * @param string $phone 手机号
     * @param string $type 验证码类型
     * @return array 发送结果
     */
    public function sendSmsCode(string $phone, string $type = 'register'): array
    {
        if (!$this->smsService) {
            return [
                'success' => false,
                'message' => '短信服务不可用',
            ];
        }

        try {
            // 验证手机号格式
            if (!$this->isValidPhone($phone)) {
                return [
                    'success' => false,
                    'message' => '手机号格式不正确',
                ];
            }

            // 检查发送频率限制
            if (!$this->checkSendLimit($phone, 'sms')) {
                return [
                    'success' => false,
                    'message' => '发送过于频繁，请稍后再试',
                ];
            }

            // 生成验证码
            $code = $this->generateCode($this->config['sms']['length']);

            // 缓存验证码
            $this->cacheCode($phone, $code, $type, 'sms');

            // 直接从通知模块获取验证码模板配置，然后调用Sms模块发送
            // 这样避免了通过NotificationService的完整流程（用户设置检查等）
            $template = NotificationTemplate::where('code', 'verification_code_sms')
                ->where('status', true)
                ->first();

            if (!$template || !$template->sms_template_id) {
                Log::error('验证码模板未配置', [
                    'phone' => $phone,
                    'template_exists' => $template !== null,
                ]);

                return [
                    'success' => false,
                    'message' => '验证码服务配置错误',
                ];
            }

            // 计算有效期（分钟）
            $expireMinutes = (int)($this->config['sms']['ttl'] / 60);

            // 直接调用Sms模块发送，传递模板ID、变量顺序和模板数据
            $result = $this->smsServiceImpl->sendWithTemplateId(
                $phone,
                $template->sms_template_id,
                [
                    'code' => $code,
                    'expire' => (string)$expireMinutes,
                ],
                null, // driver
                $template->variables ?? [] // 变量顺序
            );

            if ($result['success']) {
                // 记录发送限制
                $this->recordSendLimit($phone, 'sms');

                Log::info('短信验证码发送成功', [
                    'phone' => $phone,
                    'type' => $type,
                    'code_length' => strlen($code),
                ]);

                return [
                    'success' => true,
                    'message' => '验证码发送成功',
                    'data' => [
                        'phone' => $phone,
                        'ttl' => $this->config['sms']['ttl'],
                    ],
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? '验证码发送失败',
                ];
            }

        } catch (\Exception $e) {
            Log::error('短信验证码发送失败', [
                'phone' => $phone,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '验证码发送失败，请稍后再试',
            ];
        }
    }

    /**
     * 验证邮箱验证码
     *
     * @param string $email 邮箱地址
     * @param string $code 用户输入的验证码
     * @param string $type 验证码类型
     * @return bool 验证结果
     * @throws AuthException
     */
    public function verifyEmailCode(string $email, string $code, string $type = 'register'): bool
    {
        return $this->verifyCode($email, $code, $type, 'email');
    }

    /**
     * 验证短信验证码
     *
     * @param string $phone 手机号
     * @param string $code 用户输入的验证码
     * @param string $type 验证码类型
     * @return bool 验证结果
     * @throws AuthException
     */
    public function verifySmsCode(string $phone, string $code, string $type = 'register'): bool
    {
        return $this->verifyCode($phone, $code, $type, 'sms');
    }

    /**
     * 通用验证码验证方法
     *
     * @param string $identifier 标识符（邮箱或手机号）
     * @param string $code 用户输入的验证码
     * @param string $type 验证码类型
     * @param string $method 发送方式 (email|sms)
     * @return bool 验证结果
     * @throws AuthException
     */
    protected function verifyCode(string $identifier, string $code, string $type, string $method): bool
    {
        $config = $this->config[$method];
        $cacheKey = $this->getCacheKey($identifier, $type, $method);
        $attemptsKey = $cacheKey . ':attempts';

        // 获取缓存的验证码信息
        $cachedData = Cache::get($cacheKey);

        if (!$cachedData) {
            throw AuthException::verificationCodeExpired();
        }

        // 检查验证次数
        $attempts = Cache::get($attemptsKey, 0);
        if ($attempts >= $config['max_attempts']) {
            throw AuthException::tooManyVerificationAttempts();
        }

        // 增加验证次数
        Cache::increment($attemptsKey);

        // 验证验证码
        if ($cachedData['code'] !== $code) {
            throw AuthException::invalidVerificationCode();
        }

        // 验证成功，删除缓存
        Cache::forget($cacheKey);
        Cache::forget($attemptsKey);

        Log::info("{$method}验证码验证成功", [
            'identifier' => $identifier,
            'type' => $type,
        ]);

        return true;
    }

    /**
     * 生成验证码
     */
    protected function generateCode(int $length): string
    {
        return str_pad(random_int(0, (int)str_repeat('9', $length)), $length, '0', STR_PAD_LEFT);
    }

    /**
     * 缓存验证码
     */
    protected function cacheCode(string $identifier, string $code, string $type, string $method): void
    {
        $cacheKey = $this->getCacheKey($identifier, $type, $method);

        Cache::put($cacheKey, [
            'code' => $code,
            'created_at' => now(),
        ], $this->config[$method]['ttl']);
    }

    /**
     * 获取缓存键
     */
    protected function getCacheKey(string $identifier, string $type, string $method): string
    {
        return "verification:{$method}:{$type}:{$identifier}";
    }

    /**
     * 检查发送频率限制
     */
    protected function checkSendLimit(string $identifier, string $method): bool
    {
        $limitKey = "verification:send_limit:{$method}:{$identifier}";
        $lastSend = Cache::get($limitKey);

        if ($lastSend) {
            $elapsed = now()->diffInSeconds($lastSend);
            if ($elapsed < $this->config[$method]['send_limit']) {
                return false;
            }
        }

        return true;
    }

    /**
     * 记录发送限制
     */
    protected function recordSendLimit(string $identifier, string $method): void
    {
        $limitKey = "verification:send_limit:{$method}:{$identifier}";
        Cache::put($limitKey, now(), $this->config[$method]['send_limit']);
    }

    /**
     * 验证手机号格式
     */
    protected function isValidPhone(string $phone): bool
    {
        // 中国手机号正则表达式
        return preg_match('/^1[3-9]\d{9}$/', $phone);
    }

    /**
     * 发送邮件验证码
     */
    protected function sendEmail(string $email, string $code, string $type): void
    {
        // 计算有效期（分钟）
        $expireMinutes = (int)($this->config['email']['ttl'] / 60);

        // 创建简单的 notifiable 对象（需要 email 和 id 属性）
        $notifiable = new class($email) {
            public $email;
            public $id;

            public function __construct($email)
            {
                $this->email = $email;
                $this->id = null; // 验证码场景可能没有用户ID
            }
        };

        // 调用通知模块发送邮件验证码
        $notificationService = app(NotificationService::class);
        $notificationService->send(
            $notifiable,
            'verification_code_email',
            [
                'code' => $code,
                'expire' => (string)$expireMinutes,
            ],
            ['mail'] // 指定只使用邮件通道
        );
    }

    /**
     * 获取验证码配置
     */
    public function getConfig(string $method = null): array
    {
        return $method ? ($this->config[$method] ?? []) : $this->config;
    }

    /**
     * 设置验证码配置（用于测试或动态配置）
     */
    public function setConfig(string $method, array $config): void
    {
        $this->config[$method] = array_merge($this->config[$method], $config);
    }
}
