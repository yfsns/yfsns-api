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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * 检查用户是否具有管理员权限
     * 管理员权限通过用户模型的 is_admin 字段判断
     * 
     * 统一处理 Sanctum 认证：自动检测 session 或 token
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->authenticate($request);

        // 检查用户是否已认证
        if (!$user) {
            // 对于 web 请求，重定向到登录页面
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'code' => 401,
                    'message' => '请先登录或登录已过期',
                    'data' => null,
                ], 401);
            } else {
                return redirect('/admin/login');
            }
        }

        // 检查用户是否具有管理员权限
        if (!$user->is_admin) {
            // 对于 web 请求，重定向到登录页面
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'code' => 403,
                    'message' => '需要管理员权限',
                    'data' => null,
                ], 403);
            } else {
                return redirect('/admin/login')->with('error', '需要管理员权限');
            }
        }

        // 将用户设置到请求中，供后续中间件和控制器使用
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }

    /**
     * 统一的 Sanctum 认证逻辑
     * 自动检测 session 或 token
     */
    protected function authenticate(Request $request)
    {
        // 1. 尝试从 session 获取用户（SPA 模式）
        $guards = config('sanctum.guard', ['web']);
        foreach ($guards as $guard) {
            $user = Auth::guard($guard)->user();
            if ($user) {
                return $user;
            }
        }

        // 2. 尝试从 Bearer token 获取用户（API 模式）
        $token = $request->bearerToken();
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                return $accessToken->tokenable;
            }
        }

        return null;
    }
}
