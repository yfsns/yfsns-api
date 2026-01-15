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

namespace App\Modules\Sms\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sms\Config\SmsConfigManager;
use App\Modules\Sms\Models\SmsConfig;
use App\Modules\Sms\Models\SmsTemplate;
use App\Modules\Sms\Services\SmsService;
use App\Modules\Sms\Http\Resources\SmsChannelCollection;
use App\Modules\Sms\Http\Resources\SmsConfigResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group admin-后台管理-短信管理
 *
 * @name 短信管理
 *
 * @description 短信管理相关接口
 */
class SmsController extends Controller
{
    protected SmsService $smsService;
    protected SmsConfigManager $configManager;

    public function __construct(SmsService $smsService, SmsConfigManager $configManager)
    {
        $this->smsService = $smsService;
        $this->configManager = $configManager;
    }

    /**
     * 发送测试短信
     *
     * @authenticated
     *
     * @bodyParam phone string required 手机号码 Example: 13800138000
     * @bodyParam template_code string required 模板代码 Example: verification_code
     * @bodyParam template_data array required 模板数据 Example: {"code":"123456"}
     *
     * @response {
     *  "code": 0,
     *  "message": "短信发送成功",
     *  "data": {
     *    "phone": "13800138000",
     *    "content": "这是一条测试短信",
     *    "status": "success",
     *    "details": {
     *      "request_id": "xxx",
     *      "biz_id": "xxx"
     *    }
     *  }
     * }
     */
    public function test(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^1[3-9]\d{9}$/',
            'template_code' => 'required|string',
            'template_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => '参数验证失败',
                'data' => [
                    'phone' => $request->phone,
                    'status' => 'failed',
                    'error_type' => 'validation_error',
                    'errors' => $validator->errors(),
                ],
            ], 422);
        }

        // 获取短信模板
        $template = SmsTemplate::where('code', $request->template_code)
            ->where('status', 1)
            ->first();

        if (! $template) {
            return response()->json([
                'code' => 404,
                'message' => '短信模板不存在或已禁用',
                'data' => [
                    'phone' => $request->phone,
                    'template_code' => $request->template_code,
                    'status' => 'failed',
                    'error_type' => 'template_not_found',
                ],
            ], 404);
        }

        // 验证模板参数
        $missingParams = array_diff($template->variables, array_keys($request->template_data));
        if (! empty($missingParams)) {
            return response()->json([
                'code' => 422,
                'message' => '模板参数不完整',
                'data' => [
                    'phone' => $request->phone,
                    'template_code' => $request->template_code,
                    'missing_params' => $missingParams,
                    'status' => 'failed',
                    'error_type' => 'template_params_missing',
                ],
            ], 422);
        }

        $result = $this->smsService->send(
            $request->phone,
            $request->template_code,
            $request->template_data
        );

        if (! $result['success']) {
            return response()->json([
                'code' => 500,
                'message' => $result['message'],
                'data' => [
                    'phone' => $request->phone,
                    'template_code' => $request->template_code,
                    'status' => 'failed',
                    'error_type' => 'sms_service_error',
                    'details' => $result['data'] ?? null,
                ],
            ], 500);
        }

        return response()->json([
            'code' => 200,
            'message' => '短信发送成功',
            'data' => [
                'phone' => $request->phone,
                'template_code' => $request->template_code,
                'content' => $template->replaceVariables($request->template_data),
                'status' => 'success',
                'details' => $result['data'] ?? null,
            ],
        ], 200);
    }

    /**
     * 获取当前正在使用的短信配置.
     *
     * @authenticated
     *
     * @response {
     *  "code": 0,
     *  "message": "success",
     *  "data": {
     *    "driver": "aliyun",
     *    "name": "阿里云短信",
     *    "config": {
     *      "access_key_id": "xxx",
     *      "access_key_secret": "xxx",
     *      "sign_name": "xxx",
     *      "region_id": "xxx"
     *    },
     *    "status": true
     *  }
     * }
     */
    public function getCurrent(): JsonResponse
    {
        $config = SmsConfig::getEnabled();

        if (! $config) {
            return response()->json([
                'code' => 404,
                'message' => '短信服务未配置或未启用',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => new SmsConfigResource($config),
        ], 200);
    }


    /**
     * 获取所有短信通道列表（包括禁用的）.
     *
     * @authenticated
     *
     * @response {
     *  "code": 0,
     *  "message": "success",
     *  "data": {
     *    "configs": [
     *      {
     *        "id": 1,
     *        "name": "阿里云短信",
     *        "driver": "aliyun",
     *        "status": true,
     *        "config": {...},
     *        "created_at": "2024-01-01T00:00:00.000000Z",
     *        "updated_at": "2024-01-01T00:00:00.000000Z"
     *      }
     *    ],
     *    "available_channels": {
     *      "aliyun": {
     *        "type": "aliyun",
     *        "name": "阿里云短信",
     *        "capabilities": ["verification", "notification"],
     *        "is_builtin": true,
     *        "config_fields": {...}
     *      }
     *    },
     *    "channel_statuses": {
     *      "aliyun": {
     *        "channel_type": "aliyun",
     *        "configured": true,
     *        "enabled": true,
     *        "available": true
     *      }
     *    }
     *  }
     * }
     */
    public function getChannels(): JsonResponse
    {
        // 获取已配置的通道
        $configs = SmsConfig::orderBy('created_at', 'asc')->get();

        // 获取所有可用通道信息
        $availableChannels = $this->smsService->getAvailableChannels();

        // 获取通道状态
        $channelStatuses = $this->smsService->getAllChannelStatuses();

        return response()->json([
            'code' => 200,
            'message' => '获取通道列表成功',
            'data' => new SmsChannelCollection($configs, [
                'available_channels' => $availableChannels,
                'channel_statuses' => $channelStatuses,
            ]),
        ], 200);
    }

    /**
     * 启用短信通道
     * 同时只能有一个短信通道处于启用状态
     *
     * @param int $id 短信配置ID
     */
    public function enable($id): JsonResponse
    {
        $config = SmsConfig::findOrFail($id);

        // 禁用其他所有短信配置（同时只能启用一个）
        SmsConfig::where('id', '!=', $config->id)->update(['status' => 0]);

        // 启用当前配置
        $config->update(['status' => 1]);

        return response()->json([
            'code' => 200,
            'message' => '短信通道已启用',
            'data' => [
                'id' => $config->id,
                'name' => $config->name,
                'status' => $config->status,
            ],
        ], 200);
    }
}
