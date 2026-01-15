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

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Requests\BindEmailRequest;
use App\Modules\User\Requests\BindPhoneRequest;
use App\Modules\User\Requests\BindWechatRequest;
use App\Modules\User\Requests\ChangePasswordRequest;
use App\Modules\User\Services\SecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 账号安全
 *
 * 提供账号安全相关的接口，包括绑定/解绑手机、邮箱、微信，修改密码等功能
 *
 * @authenticated
 */
class SecurityController extends Controller
{
    protected SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * 绑定手机.
     *
     * 绑定用户的手机号码
     *
     * @bodyParam phone string required 手机号码. Example: 13800138000
     * @bodyParam code string required 验证码. Example: 123456
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "手机绑定成功",
     *     "data": {
     *         "phone": "13800138000"
     *     }
     * }
     * @response 401 {
     *     "code": 401,
     *     "message": "未授权",
     *     "data": null
     * }
     * @response 422 {
     *     "code": 422,
     *     "message": "验证失败",
     *     "data": {
     *         "phone": ["手机号码格式不正确"],
     *         "code": ["验证码错误"]
     *     }
     * }
     */
    public function bindPhone(BindPhoneRequest $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->securityService->bindPhone($user, $request->validated());

        return response()->success($result, '操作成功');
    }

    /**
     * 解绑手机.
     *
     * 解绑用户的手机号码
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "手机解绑成功",
     *     "data": null
     * }
     * @response 401 {
     *     "code": 401,
     *     "message": "未授权",
     *     "data": null
     * }
     */
    public function unbindPhone(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->securityService->unbindPhone($user);

        return response()->success($result, '操作成功');
    }

    /**
     * 绑定或换绑邮箱.
     *
     * 绑定/换绑用户的邮箱地址
     * - 如果未绑定邮箱，则为绑定
     * - 如果已绑定邮箱，则为换绑
     *
     * @bodyParam email string required 邮箱地址. Example: user@example.com
     * @bodyParam code string required 验证码. Example: 123456
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "邮箱绑定成功",
     *     "data": {
     *         "email": "user@example.com",
     *         "isRebind": false,
     *         "oldEmail": null
     *     }
     * }
     * @response 401 {
     *     "code": 401,
     *     "message": "未授权",
     *     "data": null
     * }
     * @response 422 {
     *     "code": 422,
     *     "message": "验证失败",
     *     "data": {
     *         "email": ["邮箱格式不正确"],
     *         "code": ["验证码错误"]
     *     }
     * }
     */
    public function bindEmail(BindEmailRequest $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->securityService->bindEmail($user, $request->validated());

        return response()->json([
            'email' => $result['email'],
            'isRebind' => $result['is_rebind'],
            'oldEmail' => $result['old_email'],
        ], 200);
    }

    /**
     * 绑定微信
     *
     * 绑定用户的微信账号
     *
     * @bodyParam code string required 微信授权码. Example: 123456
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "微信绑定成功",
     *     "data": {
     *         "wechat_openid": "wx_openid_123456"
     *     }
     * }
     * @response 401 {
     *     "code": 401,
     *     "message": "未授权",
     *     "data": null
     * }
     * @response 422 {
     *     "code": 422,
     *     "message": "验证失败",
     *     "data": {
     *         "code": ["微信授权码无效"]
     *     }
     * }
     */
    public function bindWechat(BindWechatRequest $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->securityService->bindWechat($user, $request->validated());

        return response()->success($result, '操作成功');
    }

    /**
     * 解绑微信
     *
     * 解绑用户的微信账号
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "微信解绑成功",
     *     "data": null
     * }
     * @response 401 {
     *     "code": 401,
     *     "message": "未授权",
     *     "data": null
     * }
     */
    public function unbindWechat(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->securityService->unbindWechat($user);

        return response()->success($result, '操作成功');
    }

    /**
     * 修改密码
     *
     * 修改用户的登录密码
     *
     * @bodyParam old_password string required 旧密码. Example: oldpass123
     * @bodyParam password string required 新密码. Example: newpass123
     * @bodyParam password_confirmation string required 确认新密码. Example: newpass123
     *
     * @response 200 {
     *     "code": 200,
     *     "message": "密码修改成功",
     *     "data": null
     * }
     * @response 401 {
     *     "code": 401,
     *     "message": "未授权",
     *     "data": null
     * }
     * @response 422 {
     *     "code": 422,
     *     "message": "验证失败",
     *     "data": {
     *         "old_password": ["旧密码错误"],
     *         "password": ["新密码格式不正确"],
     *         "password_confirmation": ["两次输入的密码不一致"]
     *     }
     * }
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        \Log::info('SecurityController changePassword called', [
            'user_id' => $request->user()?->id,
            'headers' => $request->headers->all(),
            'data' => $request->validated()
        ]);

        $user = $request->user();
        if (!$user) {
            \Log::warning('No authenticated user for change password');
            return response()->error('用户未认证', 401);
        }

        $result = $this->securityService->changePassword($user, $request->validated());
        \Log::info('Password change successful', ['user_id' => $user->id]);

        return response()->success($result, '操作成功');
    }
}
