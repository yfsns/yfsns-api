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

namespace App\Modules\System\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 认证配置资源
 *
 * 用于格式化认证配置数据为前端友好的驼峰格式
 */
class AuthConfigResource extends JsonResource
{
    /**
     * 转换资源为数组
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $authConfigService = app(\App\Modules\System\Services\AuthConfigService::class);

        return [
            // 基本配置（驼峰格式）
            'registrationMethods' => $this->resource['registration_methods'],
            'loginMethods' => $this->resource['login_methods'],
            'passwordStrength' => $this->resource['password_strength'],
            'loginAttemptsLimit' => $this->resource['login_attempts_limit'],
            'loginLockoutDuration' => $this->resource['login_lockout_duration'],

            // 选项列表（驼峰格式）
            'options' => [
                'registrationMethods' => $authConfigService->getSupportedRegistrationMethods(),
                'loginMethods' => $authConfigService->getSupportedLoginMethods(),
                'passwordStrengthOptions' => $authConfigService->getPasswordStrengthOptions(),
            ],
        ];
    }
}
