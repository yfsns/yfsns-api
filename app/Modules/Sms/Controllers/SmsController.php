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

namespace App\Modules\Sms\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sms\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 短信模块
 *
 * SMS模块只负责短信发送能力，不包含验证码业务逻辑
 * 验证码的发送和验证请使用认证模块的接口：
 * - POST /api/v1/auth/register/verification/sms - 发送注册验证码
 * - POST /api/v1/auth/sms/verification - 发送登录验证码
 */
class SmsController extends Controller
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * 获取可用通道列表
     *
     * @return JsonResponse
     */
    public function getChannels(): JsonResponse
    {
        $channels = $this->smsService->getAvailableChannels();

        return response()->json([
            'code' => 200,
            'message' => '获取通道列表成功',
            'data' => $channels,
        ], 200);
    }

    /**
     * 发送短信
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @bodyParam phone string required 手机号
     * @bodyParam template_code string required 模板代码
     * @bodyParam data object 模板数据
     * @bodyParam channel_type string 可选的通道类型，不传则使用默认通道
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'template_code' => 'required|string',
            'data' => 'sometimes|array',
            'channel_type' => 'sometimes|string',
        ]);

        $result = $this->smsService->send(
            $request->input('phone'),
            $request->input('template_code'),
            $request->input('data', []),
            $request->input('channel_type')
        );

        if ($result['success']) {
            return response()->json([
                'code' => 200,
                'message' => $result['message'],
                'data' => $result['data'],
            ], 200);
        }

        return response()->json([
            'code' => 400,
            'message' => $result['message'],
            'data' => $result['data'],
        ], 400);
    }

    /**
     * 发送通知短信
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @bodyParam phone string required 手机号
     * @bodyParam title string required 通知标题
     * @bodyParam content string required 通知内容
     * @bodyParam channel_type string 可选的通道类型
     */
    public function sendNotification(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'title' => 'required|string|max:50',
            'content' => 'required|string|max:500',
            'channel_type' => 'sometimes|string',
        ]);

        $result = $this->smsService->sendNotification(
            $request->input('phone'),
            $request->input('title'),
            $request->input('content'),
            $request->input('channel_type')
        );

        if ($result['success']) {
            return response()->json([
                'code' => 200,
                'message' => $result['message'],
                'data' => $result['data'],
            ], 200);
        }

        return response()->json([
            'code' => 400,
            'message' => $result['message'],
            'data' => $result['data'],
        ], 400);
    }

    /**
     * 发送验证码短信
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @bodyParam phone string required 手机号
     * @bodyParam code string required 验证码
     * @bodyParam expire int 可选的过期时间（分钟），默认10分钟
     * @bodyParam channel_type string 可选的通道类型
     */
    public function sendVerification(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'code' => 'required|string|size:6',
            'expire' => 'sometimes|integer|min:1|max:60',
            'channel_type' => 'sometimes|string',
        ]);

        $result = $this->smsService->sendVerificationCode(
            $request->input('phone'),
            $request->input('code'),
            $request->input('expire', 10),
            $request->input('channel_type')
        );

        if ($result['success']) {
            return response()->json([
                'code' => 200,
                'message' => $result['message'],
                'data' => $result['data'],
            ], 200);
        }

        return response()->json([
            'code' => 400,
            'message' => $result['message'],
            'data' => $result['data'],
        ], 400);
    }

    /**
     * 批量发送短信
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @bodyParam messages array required 短信消息数组
     * @bodyParam messages[].phone string required 手机号
     * @bodyParam messages[].template_code string required 模板代码
     * @bodyParam messages[].data object 模板数据
     * @bodyParam channel_type string 可选的通道类型
     */
    public function sendBatch(Request $request): JsonResponse
    {
        $request->validate([
            'messages' => 'required|array|min:1|max:100',
            'messages.*.phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'messages.*.template_code' => 'required|string',
            'messages.*.data' => 'sometimes|array',
            'channel_type' => 'sometimes|string',
        ]);

        $result = $this->smsService->sendBatch(
            $request->input('messages'),
            $request->input('channel_type')
        );

        return response()->json([
            'code' => 200,
            'message' => '批量发送完成',
            'data' => $result,
        ], 200);
    }
}
