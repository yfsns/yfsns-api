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

use App\Modules\Sms\Models\SmsConfig;
use Exception;
use Illuminate\Support\Facades\Log;

use const JSON_UNESCAPED_UNICODE;

use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Sms\V20210111\Models\SendSmsRequest;
use TencentCloud\Sms\V20210111\SmsClient;

class TencentSmsDriver implements SmsDriverInterface
{
    protected $config;

    protected $client;

    public function __construct(\Illuminate\Contracts\Container\Container $app)
    {
        $this->config = SmsConfig::getConfig('tencent');
        if (! $this->config) {
            throw new Exception('腾讯云短信配置不存在或已禁用');
        }

        Log::error('腾讯云短信构造函数 - 配置是否存在: ' . ($this->config ? '是' : '否'));
        if ($this->config) {
            Log::error('腾讯云短信构造函数 - region_id: ' . $this->config->config['region_id']);
            Log::error('腾讯云短信构造函数 - 完整配置: ' . json_encode($this->config->config, JSON_UNESCAPED_UNICODE));
            $this->initClient();
        }
    }

    public function getName(): string
    {
        return 'tencent';
    }

    public function getConfig(): array
    {
        return $this->config ? $this->config->config : [];
    }

    public function send(string $phone, string $templateCode, array $templateData = []): array
    {
        try {
            if (! $this->config) {
                throw new Exception('腾讯云短信配置不存在或已禁用');
            }

            // 检查配置是否有效
            if (! isset($this->config->config['region_id'])) {
                Log::error('腾讯云短信配置缺少 region_id');

                throw new Exception('腾讯云短信配置缺少 region_id');
            }

            if (! isset($this->config->config['sdk_app_id'])) {
                Log::error('腾讯云短信配置缺少 sdk_app_id');

                throw new Exception('腾讯云短信配置缺少 sdk_app_id');
            }

            if (! isset($this->config->config['sign_name'])) {
                Log::error('腾讯云短信配置缺少 sign_name');

                throw new Exception('腾讯云短信配置缺少 sign_name');
            }

            // 记录发送时的配置
            Log::error('腾讯云短信发送配置 - region_id: ' . $this->config->config['region_id']);
            Log::error('腾讯云短信发送配置 - 完整配置: ' . json_encode($this->config->config, JSON_UNESCAPED_UNICODE));

            $req = new SendSmsRequest();

            // 设置请求参数
            $req->setSmsSdkAppId($this->config->config['sdk_app_id']);
            $req->setSignName($this->config->config['sign_name']);
            $req->setTemplateId($templateCode);
            $req->setPhoneNumberSet(['+86' . $phone]);

            // 腾讯云要求模板参数必须是数组，不能是关联数组
            // 确保按照顺序传递参数
            $templateParams = [];
            if (isset($templateData['code'])) {
                // 验证码模板，第一个参数是验证码，第二个参数是有效期
                $templateParams[] = (string) $templateData['code'];
                $templateParams[] = '10'; // 有效期（分钟）
            } else {
                // 其他模板，按照顺序处理参数
                $i = 1;
                while (isset($templateData[$i])) {
                    $templateParams[] = (string) $templateData[$i];
                    $i++;
                }
            }
            $req->setTemplateParamSet($templateParams);

            // 记录请求参数
            Log::error('腾讯云短信请求参数：', [
                'phone' => $phone,
                'templateCode' => $templateCode,
                'templateData' => $templateData,
                'templateParams' => $templateParams,
                'request' => [
                    'SmsSdkAppId' => $this->config->config['sdk_app_id'],
                    'SignName' => $this->config->config['sign_name'],
                    'TemplateId' => $templateCode,
                    'PhoneNumberSet' => ['+86' . $phone],
                    'TemplateParamSet' => $templateParams,
                ],
            ]);

            $resp = $this->client->SendSms($req);

            // 记录响应数据
            Log::error('腾讯云短信响应数据：' . json_encode($resp, JSON_UNESCAPED_UNICODE));

            // 获取发送状态集合
            $sendStatusSet = $resp->SendStatusSet;

            if (empty($sendStatusSet)) {
                Log::error('腾讯云短信响应数据无效：SendStatusSet 为空');

                return [
                    'success' => false,
                    'message' => '短信服务响应数据无效',
                    'data' => ['error' => '发送状态为空'],
                ];
            }

            // 获取第一个发送状态
            $firstStatus = $sendStatusSet[0];

            // 记录发送状态
            Log::error('腾讯云短信发送状态：', [
                'Code' => $firstStatus->Code,
                'Message' => $firstStatus->Message,
                'SerialNo' => $firstStatus->SerialNo,
            ]);

            // 腾讯云返回的成功状态码是 "Ok"
            if ($firstStatus->Code === 'Ok') {
                return [
                    'success' => true,
                    'message' => $firstStatus->Message,
                    'data' => [
                        'serial_no' => $firstStatus->SerialNo,
                        'status' => $firstStatus->Code,
                        'message' => $firstStatus->Message,
                    ],
                ];
            }

            $errorMessage = match ($firstStatus->Code) {
                'FailedOperation.PhoneNumberInBlacklist' => '手机号在黑名单中',
                'FailedOperation.SignatureIncorrectOrUnapproved' => '签名不正确或未审核通过',
                'FailedOperation.TemplateIncorrectOrUnapproved' => '模板不正确或未审核通过',
                'FailedOperation.InsufficientBalanceInSmsPackage' => '短信包余额不足',
                'FailedOperation.MissingSignatureToModify' => '缺少签名信息',
                'FailedOperation.MissingTemplateToModify' => '缺少模板信息',
                'FailedOperation.PhoneNumberFormatIncorrect' => '手机号格式不正确',
                'FailedOperation.TemplateParameterFormatIncorrect' => '模板参数格式不正确',
                'FailedOperation.TemplateParameterLengthIncorrect' => '模板参数长度不正确',
                'FailedOperation.TemplateParameterValueIncorrect' => '模板参数值不正确',
                'FailedOperation.TemplateParameterValueMissing' => '模板参数值缺失',
                'FailedOperation.TemplateParameterValueTooLong' => '模板参数值过长',
                'FailedOperation.TemplateParameterValueTooShort' => '模板参数值过短',
                'FailedOperation.TemplateParameterValueTypeIncorrect' => '模板参数值类型不正确',
                'FailedOperation.TemplateParameterValueUnsupported' => '模板参数值不支持',
                'FailedOperation.TemplateParameterValueUnsupportedType' => '模板参数值类型不支持',
                'FailedOperation.TemplateParameterValueUnsupportedFormat' => '模板参数值格式不支持',
                'FailedOperation.TemplateParameterValueUnsupportedLength' => '模板参数值长度不支持',
                'FailedOperation.TemplateParameterValueUnsupportedRange' => '模板参数值范围不支持',
                'FailedOperation.TemplateParameterValueUnsupportedCharacter' => '模板参数值包含不支持字符',
                'FailedOperation.TemplateParameterValueUnsupportedLanguage' => '模板参数值语言不支持',
                'FailedOperation.TemplateParameterValueUnsupportedCountry' => '模板参数值国家不支持',
                'FailedOperation.TemplateParameterValueUnsupportedRegion' => '模板参数值地区不支持',
                'FailedOperation.TemplateParameterValueUnsupportedTimeZone' => '模板参数值时区不支持',
                'FailedOperation.TemplateParameterValueUnsupportedCurrency' => '模板参数值货币不支持',
                'FailedOperation.TemplateParameterValueUnsupportedUnit' => '模板参数值单位不支持',
                default => '短信发送失败，请稍后重试'
            };

            return [
                'success' => false,
                'message' => $errorMessage,
                'data' => [
                    'serial_no' => $firstStatus->SerialNo ?? '',
                    'status' => $firstStatus->Code ?? '',
                    'message' => $errorMessage,
                ],
            ];
        } catch (Exception $e) {
            Log::error('腾讯云短信发送异常：' . $e->getMessage());

            return [
                'success' => false,
                'message' => '短信服务异常',
                'data' => ['error' => $e->getMessage()],
            ];
        }
    }

    private function initClient(): void
    {
        if (! $this->client) {
            // 实例化一个认证对象，入参需要传入腾讯云账户密钥对 secretId，secretKey
            $cred = new Credential($this->config->config['secret_id'], $this->config->config['secret_key']);

            // 实例化一个 http 选项，可选，没有特殊需求可以跳过
            $httpProfile = new HttpProfile();
            // 配置代理
            // $httpProfile->setProxy("https://ip:port");
            // 支持 http/https 协议，默认为 https
            $httpProfile->setReqMethod('POST');
            // 设置请求超时时间，单位为秒
            $httpProfile->setReqTimeout(30);
            // 指定接入地域域名，默认就近地域接入
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');

            // 实例化一个client选项，可选，没有特殊需求可以跳过
            $clientProfile = new ClientProfile();
            // 指定签名算法，默认为 HmacSHA256
            $clientProfile->setSignMethod('TC3-HMAC-SHA256');
            $clientProfile->setHttpProfile($httpProfile);

            // 实例化要请求产品(以sms为例)的client对象
            // 第二个参数是地域信息，可以直接填写字符串ap-guangzhou，支持的地域列表参考 https://cloud.tencent.com/document/api/382/52071#.E5.9C.B0.E5.9F.9F.E5.88.97.E8.A1.A8
            $this->client = new SmsClient($cred, $this->config->config['region_id'], $clientProfile);
        }
    }
}
