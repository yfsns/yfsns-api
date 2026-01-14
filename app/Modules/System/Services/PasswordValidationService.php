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

/**
 * 密码验证服务
 *
 * 专门处理密码强度验证和相关规则
 */
class PasswordValidationService
{
    /**
     * 密码强度级别常量.
     */
    public const LEVEL_WEAK = 'weak';
    public const LEVEL_MEDIUM = 'medium';
    public const LEVEL_STRONG = 'strong';

    /**
     * 获取密码强度级别.
     */
    public function getPasswordStrengthLevel(): string
    {
        // 优先从系统配置中读取，如果没有则使用默认配置
        return \App\Modules\System\Models\Config::getValue('password_strength', 'auth') ??
               config('password.strength_level', self::LEVEL_MEDIUM);
    }

    /**
     * 获取密码强度选项列表.
     */
    public function getPasswordStrengthOptions(): array
    {
        return config('password.schema.strength_level.options', [
            [
                'value' => 'weak',
                'label' => '弱密码',
                'description' => '长度大于等于6个字符',
            ],
            [
                'value' => 'medium',
                'label' => '中等密码',
                'description' => '包含字母和数字，长度大于等于6个字符',
            ],
            [
                'value' => 'strong',
                'label' => '强密码',
                'description' => '包含大小写字母、数字和特殊符号，长度大于等于8个字符',
            ],
        ]);
    }

    /**
     * 获取密码强度验证规则（用于Laravel表单验证）
     */
    public function getPasswordStrengthRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            $errors = $this->validatePasswordStrength($value);

            foreach ($errors as $error) {
                $fail($error);
            }
        };
    }

    /**
     * 验证密码强度.
     */
    public function validatePasswordStrength(string $password, ?string $level = null): array
    {
        $errors = [];

        // 如果未指定级别，从配置文件中读取
        if ($level === null) {
            $level = config('password.strength_level', self::LEVEL_WEAK);
        }

        $minLength = config('password.min_length', 6);

        switch ($level) {
            case self::LEVEL_WEAK:
                // 弱密码：长度>=最小长度
                if (strlen($password) < $minLength) {
                    $errors[] = "密码长度不能少于{$minLength}个字符";
                }
                break;

            case self::LEVEL_MEDIUM:
                // 中等密码：字母+数字，长度>=最小长度
                if (strlen($password) < $minLength) {
                    $errors[] = "密码长度不能少于{$minLength}个字符";
                    break;
                }

                $hasLetter = preg_match('/[a-zA-Z]/', $password);
                $hasNumber = preg_match('/[0-9]/', $password);

                if (!$hasLetter || !$hasNumber) {
                    $errors[] = '密码必须包含字母和数字';
                }
                break;

            case self::LEVEL_STRONG:
                // 强密码：大小写+数字+符号，长度>=8
                $strongMinLength = max($minLength, 8);
                if (strlen($password) < $strongMinLength) {
                    $errors[] = "密码长度不能少于{$strongMinLength}个字符";
                    break;
                }

                $hasLowercase = preg_match('/[a-z]/', $password);
                $hasUppercase = preg_match('/[A-Z]/', $password);
                $hasNumber = preg_match('/[0-9]/', $password);
                $hasSymbol = preg_match('/[^a-zA-Z0-9]/', $password);

                $missing = [];
                if (!$hasLowercase) {
                    $missing[] = '小写字母';
                }
                if (!$hasUppercase) {
                    $missing[] = '大写字母';
                }
                if (!$hasNumber) {
                    $missing[] = '数字';
                }
                if (!$hasSymbol) {
                    $missing[] = '特殊符号';
                }

                if (!empty($missing)) {
                    $errors[] = '密码必须包含大小写字母、数字和特殊符号，当前缺少：' . implode('、', $missing);
                }
                break;
        }

        return $errors;
    }

    /**
     * 检查密码是否符合当前强度要求.
     */
    public function isPasswordValid(string $password): bool
    {
        $errors = $this->validatePasswordStrength($password);
        return empty($errors);
    }

    /**
     * 获取密码强度评分（0-100）.
     */
    public function getPasswordScore(string $password): int
    {
        $score = 0;

        // 长度评分（最高20分）
        $length = strlen($password);
        if ($length >= 12) {
            $score += 20;
        } elseif ($length >= 8) {
            $score += 15;
        } elseif ($length >= 6) {
            $score += 10;
        }

        // 字符类型评分（最高80分）
        $hasLowercase = preg_match('/[a-z]/', $password);
        $hasUppercase = preg_match('/[A-Z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSymbol = preg_match('/[^a-zA-Z0-9]/', $password);

        $typeCount = ($hasLowercase ? 1 : 0) + ($hasUppercase ? 1 : 0) +
                    ($hasNumber ? 1 : 0) + ($hasSymbol ? 1 : 0);

        $score += $typeCount * 20;

        return min(100, $score);
    }

    /**
     * 获取密码强度描述.
     */
    public function getPasswordStrengthDescription(string $level): string
    {
        return match ($level) {
            self::LEVEL_WEAK => '弱密码：长度大于等于6个字符即可',
            self::LEVEL_MEDIUM => '中等密码：包含字母和数字，长度大于等于6个字符',
            self::LEVEL_STRONG => '强密码：包含大小写字母、数字和特殊符号，长度大于等于8个字符',
            default => '未知强度级别',
        };
    }
}
