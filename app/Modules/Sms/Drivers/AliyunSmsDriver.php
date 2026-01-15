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

namespace App\Modules\Sms\Drivers;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use App\Modules\Sms\Models\SmsConfig;
use Exception;
use Illuminate\Support\Facades\Log;

class AliyunSmsDriver implements SmsDriverInterface
{
    protected $config;

    public function __construct(\Illuminate\Contracts\Container\Container $app)
    {
        $this->config = SmsConfig::where('driver', 'aliyun')
            ->where('status', 1)
            ->first();

        if ($this->config) {
            AlibabaCloud::accessKeyClient(
                $this->config->config['access_key_id'],
                $this->config->config['access_key_secret']
            )->regionId($this->config->config['region_id'] ?? 'cn-hangzhou')->asDefaultClient();
        }
    }

    /**
     * 获取驱动名称.
     */
    public function getName(): string
    {
        return 'aliyun';
    }

    /**
     * 获取驱动配置.
     */
    public function getConfig(): array
    {
        return $this->config ? $this->config->config : [];
    }

    public function send(string $phone, string $templateCode, array $templateData = []): array
    {
        try {
            if (! $this->config) {
                throw new Exception('阿里云短信配置不存在或已禁用');
            }

            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->options([
                    'query' => [
                        'PhoneNumbers' => $phone,
                        'SignName' => $this->config->config['sign_name'],
                        'TemplateCode' => $templateCode,
                        'TemplateParam' => json_encode($templateData),
                    ],
                ])
                ->request();

            $response = $result->toArray();

            if ($response['Code'] === 'OK') {
                return [
                    'success' => true,
                    'message' => '发送成功',
                    'data' => $response,
                ];
            }

            return [
                'success' => false,
                'message' => $response['Message'] ?? '发送失败',
                'data' => $response,
            ];
        } catch (ClientException $e) {
            Log::error('阿里云短信客户端异常：' . $e->getMessage());

            return [
                'success' => false,
                'message' => '短信服务异常',
                'data' => ['error' => $e->getMessage()],
            ];
        } catch (ServerException $e) {
            Log::error('阿里云短信服务端异常：' . $e->getMessage());

            return [
                'success' => false,
                'message' => '短信服务异常',
                'data' => ['error' => $e->getMessage()],
            ];
        } catch (Exception $e) {
            Log::error('阿里云短信发送异常：' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => ['error' => $e->getMessage()],
            ];
        }
    }
}
