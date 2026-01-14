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

namespace App\Modules\System\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\System\Resources\AuthConfigResource;
use App\Modules\System\Services\CacheClearService;
use App\Modules\System\Services\ConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    protected $configService;
    protected $cacheClearService;

    public function __construct(
        ConfigService $configService,
        CacheClearService $cacheClearService
    ) {
        $this->configService = $configService;
        $this->cacheClearService = $cacheClearService;
    }

    /**
     * 获取单个配置
     *
     * @authenticated
     */
    public function get(string $group, string $key): JsonResponse
    {
        $value = $this->configService->get($key, $group);

        if ($value === null) {
            return response()->json([
                'code' => 404,
                'message' => '配置不存在',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'group' => $group,
                'key' => $key,
                'value' => $value,
            ],
        ], 200);
    }

    /**
     * 获取分组下所有配置
     *
     * @authenticated
     */
    public function getGroup(string $group): JsonResponse
    {
        $configs = \App\Modules\System\Models\Config::where('group', $group)->get();

        $result = [];
        foreach ($configs as $config) {
            $result[$config->key] = $this->configService->get($config->key, $group);
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'group' => $group,
                'configs' => $result,
            ],
        ], 200);
    }

    /**
     * 设置配置
     *
     * @authenticated
     */
    public function set(string $group, string $key): JsonResponse
    {
        $value = request('value');
        $type = request('type', 'string');
        $description = request('description', '');

        $config = $this->configService->set($key, $value, $type, $group, $description);

        return response()->json([
            'code' => 200,
            'message' => '设置成功',
            'data' => [
                'group' => $group,
                'key' => $key,
                'value' => $this->configService->get($key, $group),
                'type' => $config->type,
            ],
        ], 200);
    }

    /**
     * 删除配置
     *
     * @authenticated
     */
    public function delete(string $group, string $key): JsonResponse
    {
        // 清除缓存
        $this->configService->clearCache($key, $group);

        // 删除配置
        $deleted = \App\Modules\System\Models\Config::where('key', $key)
            ->where('group', $group)
            ->delete();

        if ($deleted) {
            return response()->json([
                'code' => 200,
                'message' => '删除成功',
                'data' => null,
            ], 200);
        }

        return response()->json([
            'code' => 404,
            'message' => '配置不存在',
            'data' => null,
        ], 404);
    }

    /**
     * 获取所有分组
     *
     * @authenticated
     */
    public function groups(): JsonResponse
    {
        $groups = \App\Modules\System\Models\Config::select('group')
            ->distinct()
            ->pluck('group')
            ->toArray();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'groups' => $groups,
            ],
        ], 200);
    }

    /**
     * 获取内容审核配置（分组为content）
     *
     * @authenticated
     */
    public function getContentReviewConfig(): JsonResponse
    {
        $configs = \App\Modules\System\Models\Config::where('group', 'content')
            ->orderBy('key')
            ->get();

        $result = [
            'review_settings' => null,
            'module_switches' => []
        ];

        foreach ($configs as $config) {
            $value = $this->configService->get($config->key, 'content');

            if ($config->key === 'content_review_settings') {
                $result['review_settings'] = $value;
            } else {
                $result['module_switches'][] = [
                    'module' => $config->key,
                    'enabled' => $value,
                    'description' => $config->description
                ];
            }
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'group' => 'content',
                'review_settings' => $result['review_settings'],
                'module_switches' => $result['module_switches'],
                'total_modules' => count($result['module_switches']),
            ],
        ], 200);
    }

    /**
     * 更新内容审核配置
     *
     * @authenticated
     */
    public function updateContentReviewConfig(): JsonResponse
    {
        $reviewSettings = request('review_settings');
        $moduleSwitches = request('module_switches', []);

        $successCount = 0;
        $errors = [];

        // 更新审核设置
        if ($reviewSettings !== null) {
            $result = $this->configService->set(
                'content_review_settings',
                $reviewSettings,
                'json',
                'content',
                '内容发布审核设置（JSON）'
            );
            if ($result) {
                $successCount++;
            }
        }

        // 更新模块开关
        foreach ($moduleSwitches as $moduleSwitch) {
            $result = $this->configService->set(
                $moduleSwitch['module'],
                $moduleSwitch['enabled'],
                'boolean',
                'content',
                $moduleSwitch['description'] ?? ''
            );
            if ($result) {
                $successCount++;
            }
        }

        if (empty($errors)) {
            return response()->json([
                'code' => 200,
                'message' => '更新成功',
                'data' => [
                    'updated_count' => $successCount,
                    'message' => '内容审核配置更新成功',
                ],
            ], 200);
        } else {
            return response()->json([
                'code' => 400,
                'message' => '部分更新失败: ' . implode('; ', $errors),
                'data' => [
                    'updated_count' => $successCount,
                    'errors' => $errors,
                ],
            ], 400);
        }
    }

    /**
     * 获取认证配置
     *
     * @authenticated
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取认证配置成功",
     *   "data": {
     *     "registrationMethods": ["username", "email", "sms"],
     *     "loginMethods": ["username", "email", "sms"],
     *     "passwordStrength": "medium",
     *     "loginAttemptsLimit": 5,
     *     "loginLockoutDuration": 900,
     *     "options": {
     *       "registrationMethods": {
     *         "username": "用户名注册",
     *         "email": "邮箱注册",
     *         "sms": "短信注册"
     *       },
     *       "loginMethods": {
     *         "username": "用户名登录",
     *         "email": "邮箱验证码登录",
     *         "sms": "短信验证码登录"
     *       },
     *       "passwordStrengthOptions": {
     *         "weak": "弱密码（长度≥6）",
     *         "medium": "中等密码（字母+数字，长度≥6）",
     *         "strong": "强密码（大小写+数字+符号，长度≥8）"
     *       }
     *     }
     *   }
     * }
     */
    public function getAuthConfig(): JsonResponse
    {
        $authConfigService = app(\App\Modules\System\Services\AuthConfigService::class);
        $config = $authConfigService->getAuthConfigSummary();

        return response()->json([
            'code' => 200,
            'message' => '获取认证配置成功',
            'data' => new AuthConfigResource($config)
        ], 200);
    }

    /**
     * 更新认证配置
     *
     * @authenticated
     *
     * @bodyParam registrationMethods array optional 注册方式，多选：username,email,sms，不传或空数组表示不启用任何注册方式. Example: ["username","email","sms"]
     * @bodyParam loginMethods array optional 登录方式，多选：username,email,sms，不传或空数组表示不启用任何登录方式. Example: ["username","email","sms"]
     * @bodyParam passwordStrength string optional 密码强度：weak/medium/strong，不传则使用默认值. Example: medium
     * @bodyParam loginAttemptsLimit integer optional 登录失败最大尝试次数，默认5. Example: 5
     * @bodyParam loginLockoutDuration integer optional 登录锁定持续时间（秒），默认900. Example: 900
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "认证配置更新成功",
     *   "data": {
     *     "registrationMethods": ["username", "email", "sms"],
     *     "loginMethods": ["username", "email", "sms"],
     *     "passwordStrength": "medium",
     *     "loginAttemptsLimit": 5,
     *     "loginLockoutDuration": 900,
     *     "options": {
     *       "registrationMethods": {
     *         "username": "用户名注册",
     *         "email": "邮箱注册",
     *         "sms": "短信注册"
     *       },
     *       "loginMethods": {
     *         "username": "用户名登录",
     *         "email": "邮箱验证码登录",
     *         "sms": "短信验证码登录"
     *       },
     *       "passwordStrengthOptions": {
     *         "weak": "弱密码（长度≥6）",
     *         "medium": "中等密码（字母+数字，长度≥6）",
     *         "strong": "强密码（大小写+数字+符号，长度≥8）"
     *       }
     *     }
     *   }
     * }
     */
    public function updateAuthConfig(Request $request): JsonResponse
    {
        $request->validate([
            'registrationMethods' => 'nullable|array',
            'registrationMethods.*' => 'in:username,email,sms',
            'loginMethods' => 'nullable|array',
            'loginMethods.*' => 'in:username,email,sms',
            'passwordStrength' => 'nullable|in:weak,medium,strong',
            'loginAttemptsLimit' => 'integer|min:1|max:20',
            'loginLockoutDuration' => 'integer|min:60|max:3600',
        ]);

        $configService = app(ConfigService::class);
        $authConfigService = app(\App\Modules\System\Services\AuthConfigService::class);

        // 设置注册方式，不传或空数组表示不启用任何注册方式
        $registrationMethods = $request->registrationMethods ?? [];
        $configService->set('registration_methods', implode(',', $registrationMethods), 'array', 'auth', '注册方式（多选：username,email,sms）');

        // 设置登录方式，不传或空数组表示不启用任何登录方式
        $loginMethods = $request->loginMethods ?? [];
        $configService->set('login_methods', implode(',', $loginMethods), 'array', 'auth', '登录方式（多选：username,email,sms）');

        // 设置密码强度，不传则使用当前配置或默认值
        $passwordStrength = $request->passwordStrength;
        if ($passwordStrength === null) {
            $passwordStrength = $authConfigService->getPasswordStrength();
        }
        $configService->set('password_strength', $passwordStrength, 'select', 'auth', '密码强度要求（weak/medium/strong）');

        if ($request->has('loginAttemptsLimit')) {
            $configService->set('login_attempts_limit', $request->loginAttemptsLimit, 'integer', 'auth', '登录失败最大尝试次数');
        }

        if ($request->has('loginLockoutDuration')) {
            $configService->set('login_lockout_duration', $request->loginLockoutDuration, 'integer', 'auth', '登录锁定持续时间（秒）');
        }

        $updatedConfig = $authConfigService->getAuthConfigSummary();
        return response()->json([
            'code' => 200,
            'message' => '认证配置更新成功',
            'data' => new AuthConfigResource($updatedConfig)
        ], 200);
    }

    /**
     * 获取分组统计
     *
     * @authenticated
     */
    public function stats(): JsonResponse
    {
        $stats = \App\Modules\System\Models\Config::selectRaw('`group`, COUNT(*) as count')
            ->groupBy('group')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->group => $item->count];
            });

        $total = \App\Modules\System\Models\Config::count();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'total' => $total,
                'groups' => $stats,
            ]
        ], 200);
    }

    /**
     * 一键清除全部缓存.
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 系统配置管理
     *
     * @response {
     *   "code": 200,
     *   "message": "缓存清除成功",
     *   "data": {
     *     "cleared": [
     *       "应用缓存",
     *       "配置缓存",
     *       "路由缓存",
     *       "视图缓存",
     *       "敏感词缓存",
     *       "网站配置缓存",
     *       "系统配置缓存",
     *       "内容审核配置缓存"
     *     ],
     *     "count": 8
     *   }
     * }
     */
    public function clearAllCache(): JsonResponse
    {
        $result = $this->cacheClearService->clearAll();

        return response()->json([
            'code' => 200,
            'message' => '缓存清除成功',
            'data' => $result
        ], 200);
    }
}
