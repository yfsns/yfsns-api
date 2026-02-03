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

namespace Plugins\TencentSmsPlugin\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Plugins\TencentSmsPlugin\Models\TencentSmsTemplate;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Sms\V20210111\Models\DescribeSmsTemplateListRequest;
use TencentCloud\Sms\V20210111\SmsClient;

/**
 * 腾讯云短信模板同步服务
 */
class TencentTemplateSyncService
{
    protected TencentSmsService $smsService;

    public function __construct(TencentSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * 从腾讯云同步模板列表
     */
    public function syncTemplates(): array
    {
        try {
            $config = $this->smsService->getConfig();

            if (empty($config['secret_id']) || empty($config['secret_key'])) {
                return [
                    'success' => false,
                    'message' => '腾讯云配置不完整，无法同步模板',
                    'synced_count' => 0,
                    'errors' => ['SecretId或SecretKey未配置']
                ];
            }

            // 获取腾讯云模板列表
            $platformTemplates = $this->fetchPlatformTemplates();

            if (!$platformTemplates['success']) {
                return [
                    'success' => false,
                    'message' => '获取腾讯云模板失败：' . $platformTemplates['message'],
                    'synced_count' => 0,
                    'errors' => [$platformTemplates['message']]
                ];
            }

            // 同步到本地数据库
            $syncResult = $this->syncToDatabase($platformTemplates['data']);

            return [
                'success' => true,
                'message' => "模板同步完成，共同步{$syncResult['synced']}个模板",
                'synced_count' => $syncResult['synced'],
                'errors' => $syncResult['errors']
            ];

        } catch (Exception $e) {
            Log::error('腾讯云短信模板同步异常：' . $e->getMessage(), [
                'service' => 'TencentTemplateSyncService',
                'method' => 'syncTemplates'
            ]);

            return [
                'success' => false,
                'message' => '同步失败：' . $e->getMessage(),
                'synced_count' => 0,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * 从腾讯云获取模板列表
     */
    protected function fetchPlatformTemplates(): array
    {
        try {
            $config = $this->smsService->getConfig();

            // 初始化腾讯云客户端
            $cred = new Credential($config['secret_id'], $config['secret_key']);

            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint('sms.tencentcloudapi.com');

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);

            $client = new SmsClient($cred, $config['region_id'], $clientProfile);

            // 构建查询请求
            $req = new DescribeSmsTemplateListRequest();
            $req->International = 0; // 0表示国内模板，1表示国际模板

            // 分页获取所有模板
            $allTemplates = [];
            $offset = 0;
            $limit = 100; // 每次最多获取100个

            do {
                $req->Offset = $offset;
                $req->Limit = $limit;

                $resp = $client->DescribeSmsTemplateList($req);
                $response = json_decode($resp->toJsonString(), true);

                if (!isset($response['DescribeTemplateStatusSet'])) {
                    break;
                }

                $templates = $response['DescribeTemplateStatusSet'];
                $allTemplates = array_merge($allTemplates, $templates);

                // 如果返回的模板数量小于limit，说明已经是最后一页
                if (count($templates) < $limit) {
                    break;
                }

                $offset += $limit;
            } while (true);

            return [
                'success' => true,
                'message' => '获取成功',
                'data' => $allTemplates
            ];

        } catch (TencentCloudSDKException $e) {
            Log::error('腾讯云SMS API异常：' . $e->getMessage(), [
                'service' => 'TencentTemplateSyncService',
                'method' => 'fetchPlatformTemplates'
            ]);

            return [
                'success' => false,
                'message' => 'API调用失败：' . $e->getMessage(),
                'data' => []
            ];
        } catch (Exception $e) {
            Log::error('获取腾讯云模板异常：' . $e->getMessage(), [
                'service' => 'TencentTemplateSyncService',
                'method' => 'fetchPlatformTemplates'
            ]);

            return [
                'success' => false,
                'message' => '获取失败：' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * 同步模板到数据库
     */
    protected function syncToDatabase(array $platformTemplates): array
    {
        $synced = 0;
        $errors = [];

        foreach ($platformTemplates as $template) {
            try {
                $auditStatus = $this->normalizeAuditStatus($template['Status'] ?? 0);
                $international = ($template['International'] ?? 0) === 1;

                TencentSmsTemplate::updateOrCreate(
                    ['template_id' => $template['TemplateId']],
                [
                    'template_name' => $template['TemplateName'] ?? '',
                    'template_content' => $template['TemplateContent'] ?? '',
                    'audit_status' => $auditStatus,
                    'international' => $international,
                    'status' => 1, // 只要同步过来就设为启用状态
                    'platform_data' => $template
                ]
                );

                $synced++;

            } catch (Exception $e) {
                $errorMsg = "模板{$template['TemplateId']}同步失败：" . $e->getMessage();
                Log::error($errorMsg, [
                    'service' => 'TencentTemplateSyncService',
                    'method' => 'syncToDatabase',
                    'template_id' => $template['TemplateId'] ?? 'unknown'
                ]);
                $errors[] = $errorMsg;
            }
        }

        return [
            'synced' => $synced,
            'errors' => $errors
        ];
    }

    /**
     * 标准化审核状态
     */
    protected function normalizeAuditStatus(int $status): string
    {
        switch ($status) {
            case 0:
                return 'pending';  // 待审核
            case 1:
                return 'approved'; // 通过
            case 2:
                return 'rejected'; // 拒绝
            default:
                return 'pending';
        }
    }

    /**
     * 获取本地模板列表
     */
    public function getLocalTemplates(): array
    {
        try {
            $templates = TencentSmsTemplate::where('status', 1)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'template_id' => $template->template_id,
                        'template_name' => $template->template_name,
                        'template_content' => $template->template_content,
                        'audit_status' => $template->audit_status,
                        'international' => $template->international,
                        'status' => $template->status,
                        'created_at' => $template->created_at,
                        'updated_at' => $template->updated_at
                    ];
                });

            return [
                'success' => true,
                'message' => '获取成功',
                'data' => $templates
            ];

        } catch (Exception $e) {
            Log::error('获取本地模板列表失败：' . $e->getMessage(), [
                'service' => 'TencentTemplateSyncService',
                'method' => 'getLocalTemplates'
            ]);

            return [
                'success' => false,
                'message' => '获取失败：' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * 根据模板ID查找模板
     */
    public function findTemplate(string $templateId): ?TencentSmsTemplate
    {
        return TencentSmsTemplate::where('template_id', $templateId)
            ->where('status', 1)
            ->first();
    }
}
