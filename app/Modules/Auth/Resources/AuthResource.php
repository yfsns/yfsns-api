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

namespace App\Modules\Auth\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use function in_array;

class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        // 判断客户端类型
        // 支持的客户端类型：
        // - web: 网页浏览器（使用HttpOnly Cookie）
        // - app: 移动端App（使用Bearer Token）
        // - miniapp: 小程序（使用Bearer Token）
        // - wechat-miniapp: 微信小程序（使用Bearer Token）
        // - alipay-miniapp: 支付宝小程序（使用Bearer Token）

        $clientType = $this->detectClientType($request);
        $needsToken = $clientType !== 'web';  // 非Web客户端需要返回token

        $data = [
            'user' => new UserResource($this['user']),
            'clientType' => $clientType,  // 返回识别的客户端类型（便于调试）
        ];

        // 非Web客户端（App、小程序等）需要返回 token 相关信息
        // Web 客户端使用 HttpOnly Cookie，不返回 token（更安全）
        if ($needsToken && isset($this['token_type'])) {
            $data['tokenType'] = $this['token_type'];
            $data['expiresIn'] = $this['expires_in'];
            $data['expiresAt'] = time() + $this['expires_in'];
            $data['refreshExpiresIn'] = $this['refresh_expires_in'] ?? null;
            $data['refreshExpiresAt'] = time() + ($this['refresh_expires_in'] ?? 0);

            if (isset($this['access_token'])) {
                $data['accessToken'] = $this['access_token'];
                $data['refreshToken'] = $this['refresh_token'] ?? null;
            }
        }

        return $data;
    }

    /**
     * 检测客户端类型.
     */
    protected function detectClientType($request): string
    {
        // 1. 优先检查请求头中的平台标识（如果前端主动传递）
        $platformHeader = $request->header('X-Platform');
        if ($platformHeader) {
            $allowedTypes = ['web', 'app', 'miniapp', 'wechat-miniapp', 'alipay-miniapp'];
            if (in_array($platformHeader, $allowedTypes)) {
                return $platformHeader;
            }
        }

        // 2. 备用：使用请求头 X-Client-Type
        $headerClientType = $request->header('X-Client-Type');
        if ($headerClientType) {
            $allowedTypes = ['web', 'app', 'miniapp', 'wechat-miniapp', 'alipay-miniapp'];
            if (in_array($headerClientType, $allowedTypes)) {
                return $headerClientType;
            }
        }

        // 3. 查询参数 client_type
        $queryClientType = $request->query('client_type');
        if ($queryClientType) {
            $allowedTypes = ['web', 'app', 'miniapp', 'wechat-miniapp', 'alipay-miniapp'];
            if (in_array($queryClientType, $allowedTypes)) {
                return $queryClientType;
            }
        }

        // 3. 根据 User-Agent 自动识别
        $userAgent = $request->userAgent() ?? '';

        // 微信小程序
        if (preg_match('/miniProgram|MicroMessenger.*miniprogram/i', $userAgent)) {
            return 'wechat-miniapp';
        }

        // 支付宝小程序
        if (preg_match('/AlipayClient.*TinyApp|AlipayMiniApp/i', $userAgent)) {
            return 'alipay-miniapp';
        }

        // 移动App
        if (preg_match('/\b(Android|iOS|Mobile)\b/i', $userAgent)) {
            return 'app';
        }

        // 4. 默认为 web（浏览器）
        return 'web';
    }
}
