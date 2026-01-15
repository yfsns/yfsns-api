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

namespace App\Modules\Auth\Controllers;

use App\Exceptions\AuthException;
use App\Http\Controllers\Controller;
use App\Http\Services\IpLocationService;
use App\Http\Traits\IpRecordTrait;
use App\Modules\Auth\Requests\Admin\AdminLoginRequest;
use App\Modules\Auth\Requests\Register\RegisterEmailRequest;
use App\Modules\Auth\Requests\Register\RegisterPhoneRequest;
use App\Modules\Auth\Requests\Register\RegisterUsernameRequest;
use App\Modules\Auth\Requests\Login\LoginEmailRequest;
use App\Modules\Auth\Requests\Login\LoginPhoneRequest;
use App\Modules\Auth\Requests\Login\LoginUsernameRequest;
use App\Modules\Auth\Requests\Login\LoginPasswordRequest;
use App\Modules\Auth\Resources\AuthResource;
use App\Modules\Auth\Resources\UserResource;
use App\Modules\Auth\Services\AdminAuthService;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group 统一认证接口
 *
 * 统一处理用户注册、登录、Token管理等所有认证功能
 */
class AuthController extends Controller
{
    use IpRecordTrait;

    protected AuthService $authService;
    protected AdminAuthService $adminAuthService;
    protected VerificationService $verificationService;
    protected IpLocationService $ipLocationService;

    public function __construct(
        AuthService $authService,
        AdminAuthService $adminAuthService,
        VerificationService $verificationService,
        IpLocationService $ipLocationService
    ) {
        $this->authService = $authService;
        $this->adminAuthService = $adminAuthService;
        $this->verificationService = $verificationService;
        $this->ipLocationService = $ipLocationService;
    }

    /**
     * 检查登录状态
     */
    public function check(): JsonResponse
    {
        $user = $this->authService->me();

        if ($user) {
            return response()->json([
                'authenticated' => true,
                'user' => [
                    'id' => (string) $user->id,
                    'username' => $user->username,
                    'is_admin' => (bool) $user->is_admin,
                ]
            ], 200);
        } else {
            return response()->json([
                'authenticated' => false,
                //'user' => null
            ], 200);
        }
    }

    /**
     * 发送邮箱验证码
     */
    public function sendEmailVerificationCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email:rfc,dns',
            'type' => 'sometimes|in:register,login,reset_password,change_email',
        ]);

        $result = $this->verificationService->sendEmailCode(
            $request->email,
            $request->type ?? 'register'
        );

        if ($result['success']) {
            return response()->success($result['data'], '验证码发送成功');
        } else {
            return response()->error($result['message'], 400);
        }
    }

    /**
     * 发送短信验证码
     */
    public function sendSmsVerificationCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|regex:/^1[3-9]\d{9}$/',
            'type' => 'sometimes|in:register,login,reset_password,change_phone',
        ]);

        $result = $this->verificationService->sendSmsCode(
            $request->phone,
            $request->type ?? 'register'
        );

        if ($result['success']) {
            return response()->success($result['data'], '验证码发送成功');
        } else {
            return response()->error($result['message'], 400);
        }
    }

    /**
     * 用户注册
     */
    /**
     * 邮箱注册
     */
    public function registerEmail(RegisterEmailRequest $request): JsonResponse
    {
        return $this->handleRegistration($request->validated(), 'email', $request);
    }

    /**
     * 手机号注册
     */
    public function registerPhone(RegisterPhoneRequest $request): JsonResponse
    {
        return $this->handleRegistration($request->validated(), 'phone', $request);
    }

    /**
     * 用户名注册
     */
    public function registerUsername(RegisterUsernameRequest $request): JsonResponse
    {
        return $this->handleRegistration($request->validated(), 'username', $request);
    }

    /**
     * 管理员登录（优化版，使用专用服务）
     */
    public function adminLogin(AdminLoginRequest $request): JsonResponse
    {
        $data = $this->adminAuthService->login($request->validated(), $request);

        return response()->success(new AuthResource($data), '管理员登录成功');
    }


    /**
     * 用户登录
     */
    /**
     * 邮箱登录
     */
    public function loginEmail(LoginEmailRequest $request): JsonResponse
    {
        return $this->handleVerificationLogin($request->validated(), 'email', $request);
    }

    /**
     * 手机号登录
     */
    public function loginPhone(LoginPhoneRequest $request): JsonResponse
    {
        return $this->handleVerificationLogin($request->validated(), 'phone', $request);
    }

    /**
     * 密码登录（支持用户名/邮箱/手机号+密码）
     */
    public function loginPassword(LoginPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->authService->login($data, $request);

        \Log::info('AuthController login successful', [
            'user_id' => $result['user']->id ?? 'unknown',
            'login_type' => 'password'
        ]);

        // 直接返回AuthResource的数据，避免双重包装
        // 前端拦截器会处理 {code, message, data} -> data 的转换
        return response()->success(new AuthResource($result), '登录成功');
    }

    /**
     * 用户名密码登录（保留兼容性，内部调用统一密码登录）
     * @deprecated 使用 loginPassword 代替
     */
    public function loginUsername(LoginUsernameRequest $request): JsonResponse
    {
        $data = $request->validated();
        // 将 username 转换为 account
        $data['account'] = $data['username'];
        unset($data['username']);
        
        $result = $this->authService->login($data, $request);

        \Log::info('AuthController login successful', [
            'user_id' => $result['user']->id ?? 'unknown',
            'login_type' => 'username'
        ]);

        return response()->success(new AuthResource($result), '登录成功');
    }

    /**
     * 获取当前用户信息
     */
    public function me(Request $request): JsonResponse
    {
        // 根据认证方式选择服务：管理员使用session认证，普通用户使用sanctum
        $user = $request->user() ?? Auth::user();

        if (!$user) {
            return response()->error('User not authenticated', 401);
        }

        return response()->success(new UserResource($user), '获取用户信息成功');
    }

    /**
     * 用户登出
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            // 管理员使用AdminAuthService，普通用户使用AuthService
            if ($user->is_admin) {
                $this->adminAuthService->logout($user);
            } else {
                // 传递request参数以便正确删除当前token
                $this->authService->logout($user, $request);
            }
        }

        // Laravel会自动处理session cookie的清除
        // 无需额外的cookie操作
        return response()->noContent();
    }


    /**
     * 刷新Token - 使用refresh token验证并生成新的access token
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // 获取当前token（应该是refresh token）
        $currentToken = $user->currentAccessToken();

        if (!$currentToken) {
            return response()->json(['error' => 'No valid refresh token found'], 401);
        }

        // 验证当前token是否是refresh token
        if (!in_array('refresh', $currentToken->abilities ?? [])) {
            return response()->json(['error' => 'Invalid refresh token'], 401);
        }

        // 检查refresh token是否过期
        if ($currentToken->expires_at && $currentToken->expires_at->isPast()) {
            $currentToken->delete(); // 删除过期的refresh token
            return response()->json(['error' => 'Refresh token expired'], 401);
        }

        // 删除所有旧的access tokens（保留refresh token）
        $user->tokens()->where('name', 'access-token')->delete();

        // 创建新的access token
        $newAccessToken = $user->createToken('access-token', ['*'], now()->addHour());

        return response()->json([
            'access_token' => $newAccessToken->plainTextToken,
            'refresh_token' => $request->bearerToken(), // 返回当前的refresh token
            'token_type' => 'Bearer',
            'expires_in' => 3600, // 1小时
            'refresh_expires_in' => $currentToken->expires_at ? $currentToken->expires_at->diffInSeconds(now()) : null,
        ], 200);
    }

    /**
     * 统一的注册处理逻辑
     */
    private function handleRegistration(array $data, string $type, $request): JsonResponse
    {
        // 根据注册类型验证验证码
        if (isset($data['verification_code'])) {
            if ($type === 'email') {
                $this->verificationService->verifyEmailCode(
                    $data[$type],
                    $data['verification_code'],
                    'register'
                );
            } elseif ($type === 'phone') {
                $this->verificationService->verifySmsCode(
                    $data[$type],
                    $data['verification_code'],
                    'register'
                );
            }
        }

        $result = $this->authService->register($data, $request);

        return response()->success(new AuthResource($result), '注册成功');
    }

    /**
     * 统一的验证码登录处理逻辑
     */
    private function handleVerificationLogin(array $data, string $type, $request): JsonResponse
    {
        // 验证验证码
        if ($type === 'email') {
            $this->verificationService->verifyEmailCode($data['email'], $data['verification_code'], 'login');
            $user = \App\Modules\User\Models\User::where('email', $data['email'])->first();
        } else {
            $this->verificationService->verifySmsCode($data['phone'], $data['verification_code'], 'login');
            $user = \App\Modules\User\Models\User::where('phone', $data['phone'])->first();
        }

        // 手动登录用户
        \Auth::login($user);

        // 准备认证数据
        $authData = [
            'user' => $user,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_expires_in' => 1209600,
        ];

        // 根据平台返回token
        $platform = $request->header('X-Platform', 'web');
        if ($platform === 'miniprogram' || $platform === 'app') {
            $user->tokens()->delete();
            $accessToken = $user->createToken('access-token', ['*'], now()->addHour());
            $refreshToken = $user->createToken('refresh-token', ['refresh'], now()->addDays(14));

            $authData['access_token'] = $accessToken->plainTextToken;
            $authData['refresh_token'] = $refreshToken->plainTextToken;
        }

        \Log::info('AuthController login successful', [
            'user_id' => $user->id ?? 'unknown',
            'login_type' => $type
        ]);

        return response()->success(new AuthResource($authData), '登录成功');
    }

    /**
     * 获取IP地址地理位置信息
     */
    public function getIpLocation(Request $request): JsonResponse
    {
        $request->validate([
            'ip' => 'required|ip',
        ]);

        try {
            $location = $this->ipLocationService->getLocation($request->ip);

            return response()->success([
                'ip' => $request->ip,
                'location' => $location,
            ], '获取地理位置信息成功');
        } catch (\Exception $e) {
            return response()->error('获取地理位置信息失败：' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新当前用户的IP地理位置信息
     */
    public function updateUserIpLocation(Request $request): JsonResponse
    {
        $user = $request->user() ?? Auth::user();

        if (!$user) {
            return response()->error('用户未认证', 401);
        }

        try {
            // 使用IpRecordTrait的updateUserIpLocation方法
            $this->updateUserIpLocation($user, $user->last_login_ip ?? $request->ip());

            return response()->success([
                'ip' => $user->last_login_ip,
                'location' => $user->last_login_ip_location,
                'country' => $user->ip_country,
                'region' => $user->ip_region,
                'city' => $user->ip_city,
            ], '更新地理位置信息成功');
        } catch (\Exception $e) {
            return response()->error('更新地理位置信息失败：' . $e->getMessage(), 500);
        }
    }
}