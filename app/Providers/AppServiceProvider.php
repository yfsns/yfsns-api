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

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 设置全局日期序列化格式为人类可读格式（避免每次请求都转换）
        // 这样模型在 JSON 序列化时会直接使用此格式，无需额外转换
        Carbon::serializeUsing(function ($carbon) {
            return $carbon->format('Y-m-d H:i:s');
        });

        // 为所有JSON响应添加charset=utf-8
        $this->app['events']->listen('kernel.handled', function ($request, $response) {
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                // 强制设置Content-Type为包含charset的版本
                $response->headers->set('Content-Type', 'application/json; charset=utf-8');
            }
        });

        // 添加统一的响应宏
        $this->registerResponseMacros();
    }

    /**
     * 注册响应宏
     */
    protected function registerResponseMacros(): void
    {
        // 成功响应宏
        Response::macro('success', function ($data = null, $message = '操作成功', $code = 200) {
            return response()->json([
                'code' => $code,
                'message' => $message,
                'data' => $data,
            ], $code);
        });

        // 错误响应宏
        Response::macro('error', function ($message = '操作失败', $code = 400, $data = null) {
            return response()->json([
                'code' => $code,
                'message' => $message,
                'data' => $data,
            ], $code);
        });
    }

}
