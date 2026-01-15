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

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (Throwable $e) {
            // 所有 API 路由都返回 JSON，即使浏览器直接访问也不重定向
            if (request()->expectsJson() || request()->is('api/*')) {
                if ($e instanceof ValidationException) {
                    // 记录验证错误详情，方便调试
                    Log::warning('数据验证失败', [
                        'url' => request()->url(),
                        'method' => request()->method(),
                        'errors' => $e->errors(),
                        'input' => request()->all(),
                    ]);

                    return response()->json([
                        'code' => 422,
                        'message' => '数据验证失败',
                        'data' => ['errors' => $e->errors()],
                    ], 422);
                }

                if ($e instanceof ModelNotFoundException) {
                    return response()->json([
                        'code' => 404,
                        'message' => '资源不存在或已被删除',
                        'data' => null,
                    ], 404);
                }

                if ($e instanceof NotFoundHttpException) {
                    return response()->json([
                        'code' => 404,
                        'message' => '请求的接口不存在',
                        'data' => null,
                    ], 404);
                }

                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'code' => 401,
                        'message' => '请先登录或登录已过期',
                        'data' => null,
                    ], 401);
                }

                // 处理 BaseException（包括 AuthException、BusinessException 等）
                if ($e instanceof BaseException) {
                    $statusCode = $e->getStatusCode();

                    return response()->json([
                        'code' => $statusCode,
                        'message' => $e->getMessage(),
                        'data' => $e->getData(),
                    ], $statusCode);
                }

                if ($e instanceof \App\Http\Exceptions\BusinessException) {
                    $code = $e->getCode() ?: 400;
                    $message = ($code === 404) ? '资源不存在或已被删除' : ($e->getMessage() ?: '业务异常');

                    return response()->json([
                        'code' => $code,
                        'message' => $message,
                        'data' => null,
                    ], $code);
                }

                // 处理 InvalidArgumentException（通常是参数类型不支持）
                if ($e instanceof \InvalidArgumentException) {
                    Log::warning('参数错误', [
                        'url' => request()->url(),
                        'method' => request()->method(),
                        'message' => $e->getMessage(),
                        'input' => request()->all(),
                    ]);

                    return response()->json([
                        'code' => 400,
                        'message' => $e->getMessage() ?: '参数错误',
                        'data' => null,
                    ], 400);
                }

                // 其他异常
                Log::error('服务器错误', [
                    'url' => request()->url(),
                    'method' => request()->method(),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'code' => 500,
                    'message' => config('app.debug') ? $e->getMessage() : '服务器内部错误',
                    'data' => config('app.debug') ? ['trace' => $e->getTraceAsString()] : null,
                ], 500);
            }
        });
    }

    /**
     * 处理未认证异常.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'code' => 401,
                'message' => '请先登录或登录已过期',
                'data' => null,
            ], 401);
        }

        return redirect()->guest('/admin');
    }
}
