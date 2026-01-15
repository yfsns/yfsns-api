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

namespace App\Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notification\Http\Requests\EmailConfigRequest;
use App\Modules\Notification\Http\Requests\TestEmailConfigRequest;
use App\Modules\Notification\Models\EmailConfig;
use App\Modules\Notification\Resources\EmailConfigResource;
use App\Modules\Notification\Services\EmailService;
use Exception;
use Illuminate\Http\JsonResponse;
use Log;

class EmailConfigController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * 获取邮件配置.
     *
     * @group 邮件配置管理
     *
     * @authenticated
     */
    public function show(): JsonResponse
    {
        $config = EmailConfig::first();

        // 如果没有配置，返回空数据，不报错
        if (! $config) {
            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => null,
            ], 200);
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => new EmailConfigResource($config),
        ], 200);
    }

    /**
     * 更新邮件配置.
     *
     * @group 邮件配置管理
     *
     * @authenticated
     */
    public function update(EmailConfigRequest $request): JsonResponse
    {
        $data = $request->validated();

        // 设置默认值
        $data['driver'] ??= 'smtp';
        $data['encryption'] ??= 'tls';
        $data['status'] ??= true;

        // 如果没有提供 name，设置默认名称
        if (! isset($data['name']) || empty($data['name'])) {
            $data['name'] = '默认邮件配置';
        }

        // 获取或创建配置
        $config = EmailConfig::first();

        if (! $config) {
            // 创建新配置时，密码必填（验证规则已保证）
            $config = EmailConfig::create($data);
        } else {
            // 更新配置时，如果密码为空、null 或未传，保留原密码
            if (! isset($data['password']) || $data['password'] === null || $data['password'] === '') {
                unset($data['password']);
            }
            $config->update($data);
        }

        // 重新加载配置以获取最新数据
        $config->refresh();

        return response()->json([
            'code' => 200,
            'message' => '更新成功',
            'data' => new EmailConfigResource($config),
        ], 200);
    }

    /**
     * 测试邮件配置.
     *
     * @group 邮件配置管理
     *
     * @authenticated
     */
    public function test(TestEmailConfigRequest $request): JsonResponse
    {
        $data = $request->validated();

        $config = EmailConfig::first();
        if (! $config) {
            return response()->json([
                'code' => 400,
                'message' => '邮件配置不存在，请先配置邮件服务器',
                'data' => null,
            ], 400);
        }

        // 使用当前配置发送测试邮件
        $result = $this->emailService->send(
            $data['testEmail'],
            '邮件配置测试',
            '这是一封测试邮件，用于验证邮件服务器配置是否正确。',
            $config
        );

        if ($result) {
            return response()->json([
                'code' => 200,
                'message' => '测试邮件发送成功',
                'data' => null,
            ], 200);
        }

        return response()->json([
            'code' => 500,
            'message' => '测试邮件发送失败',
            'data' => null,
        ], 500);
    }
}
