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

namespace Plugins\TencentSmsPlugin;

use App\Modules\PluginSystem\BaseServiceProviderPlugin;
use Plugins\TencentSmsPlugin\Services\TencentSmsService;
use Plugins\TencentSmsPlugin\Services\TencentTemplateSyncService;

/**
 * 腾讯云短信插件
 */
class Plugin extends BaseServiceProviderPlugin
{
    /**
     * 构造函数
     */
    public function __construct($app = null)
    {
        parent::__construct($app ?? app());
    }

    /**
     * 初始化插件
     */
    protected function initialize(): void
    {
        $this->name = 'TencentSmsPlugin';
        $this->version = '1.0.0';
        $this->description = '腾讯云短信服务插件，提供完整的腾讯云短信功能';
        $this->author = 'YFSNS Team';
        $this->requirements = [
            'php' => '>=8.1.0',
            'laravel' => '>=10.0.0',
        ];
    }

    /**
     * 插件启用时的处理
     */
    protected function onEnable(): void
    {
        parent::onEnable();

        // 执行数据库迁移
        $this->runMigrations();

        // 注册腾讯云短信服务
        $this->registerTencentSmsService();

        // 注册模板同步服务
        $this->registerTemplateSyncService();

        // 手动加载路由
        $this->registerRoutes();

        \Log::info('TencentSmsPlugin enabled successfully');
    }

    /**
     * 注册腾讯云短信服务
     */
    protected function registerTencentSmsService(): void
    {
        app()->singleton(TencentSmsService::class, function ($app) {
            return new TencentSmsService();
        });

        // 替换内置的TencentChannel
        app()->bind(\App\Modules\Sms\Contracts\SmsChannelInterface::class, function ($app) {
            // 这里可以根据配置动态选择使用插件还是内置实现
            return $app->make(TencentSmsService::class);
        });
    }

    /**
     * 注册模板同步服务
     */
    protected function registerTemplateSyncService(): void
    {
        app()->singleton(TencentTemplateSyncService::class, function ($app) {
            return new TencentTemplateSyncService(app(TencentSmsService::class));
        });
    }


    /**
     * 执行数据库迁移
     */
    protected function runMigrations(): void
    {
        $migrationPath = __DIR__ . '/Database/Migrations';

        if (is_dir($migrationPath)) {
            // 获取所有迁移文件
            $migrationFiles = glob($migrationPath . '/*.php');

            foreach ($migrationFiles as $migrationFile) {
                try {
                    // 包含迁移文件
                    $migration = require $migrationFile;

                    // 执行迁移
                    if (method_exists($migration, 'up')) {
                        $migration->up();
                        \Log::info('Executed migration: ' . basename($migrationFile));
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to execute migration ' . basename($migrationFile) . ': ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * 执行配置操作
     */
    public function executeConfigAction(string $action, string $configKey, array $params = []): array
    {
        switch ($action) {
            case 'sync_templates':
                return $this->syncTemplatesForConfig();
            default:
                return ['success' => false, 'message' => '未知操作'];
        }
    }

    /**
     * 为配置界面同步模板
     */
    protected function syncTemplatesForConfig(): array
    {
        try {
            $syncService = app(\Plugins\TencentSmsPlugin\Services\TencentTemplateSyncService::class);
            $result = $syncService->syncTemplates();

            return [
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '同步失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取数据表格数据
     */
    public function getDataTableData(string $tableKey, array $params = []): array
    {
        switch ($tableKey) {
            case 'TENCENT_SMS_TEMPLATES':
                return $this->getTemplatesTableData($params);
            default:
                return [
                    'data' => [],
                    'total' => 0,
                    'page' => 1,
                    'page_size' => 20,
                    'total_pages' => 0
                ];
        }
    }

    /**
     * 执行数据表格操作
     */
    public function executeDataTableAction(string $tableKey, string $action, array $params = []): array
    {
        switch ($tableKey) {
            case 'TENCENT_SMS_TEMPLATES':
                return $this->executeTemplatesTableAction($action, $params);
            default:
                return ['success' => false, 'message' => '未知操作'];
        }
    }

    /**
     * 更新数据表格记录
     */
    public function updateDataTableRecord(string $tableKey, $recordId, array $data): array
    {
        switch ($tableKey) {
            case 'TENCENT_SMS_TEMPLATES':
                return $this->updateTemplatesTableRecord($recordId, $data);
            default:
                return ['success' => false, 'message' => '未知表格'];
        }
    }

    /**
     * 删除数据表格记录
     */
    public function deleteDataTableRecord(string $tableKey, $recordId): array
    {
        switch ($tableKey) {
            case 'TENCENT_SMS_TEMPLATES':
                return $this->deleteTemplatesTableRecord($recordId);
            default:
                return ['success' => false, 'message' => '未知表格'];
        }
    }

    /**
     * 批量操作数据表格记录
     */
    public function batchDataTableOperation(string $tableKey, string $operation, array $recordIds): array
    {
        switch ($tableKey) {
            case 'TENCENT_SMS_TEMPLATES':
                return $this->batchTemplatesTableOperation($operation, $recordIds);
            default:
                return ['success' => false, 'message' => '未知操作'];
        }
    }

    /**
     * 获取模板表格数据
     */
    protected function getTemplatesTableData(array $params = []): array
    {
        try {
            $query = \Plugins\TencentSmsPlugin\Models\TencentSmsTemplate::query();

            // 处理搜索
            if (!empty($params['search'])) {
                $search = $params['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('template_id', 'like', "%{$search}%")
                      ->orWhere('template_name', 'like', "%{$search}%")
                      ->orWhere('template_content', 'like', "%{$search}%");
                });
            }

            // 处理筛选
            if (!empty($params['filters'])) {
                foreach ($params['filters'] as $key => $value) {
                    if ($value !== '' && $value !== null) {
                        if ($key === 'status') {
                            $query->where($key, (bool)$value);
                        } elseif ($key === 'international') {
                            $query->where($key, (bool)$value);
                        } else {
                            $query->where($key, $value);
                        }
                    }
                }
            }

            // 处理排序
            $sortField = $params['sort_field'] ?? 'updated_at';
            $sortOrder = $params['sort_order'] ?? 'desc';

            // 验证排序字段安全性
            $allowedSortFields = [
                'id', 'template_id', 'template_name', 'audit_status',
                'international', 'status', 'created_at', 'updated_at'
            ];

            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'updated_at';
            }

            $query->orderBy($sortField, $sortOrder);

            // 处理分页
            $pageSize = (int)($params['page_size'] ?? 20);
            $page = (int)($params['page'] ?? 1);

            // 限制分页大小
            $pageSize = min(max($pageSize, 10), 100);

            $paginated = $query->paginate($pageSize, ['*'], 'page', $page);

            return [
                'data' => $paginated->items(),
                'total' => $paginated->total(),
                'page' => $paginated->currentPage(),
                'page_size' => $paginated->perPage(),
                'total_pages' => $paginated->lastPage()
            ];
        } catch (\Exception $e) {
            \Log::error('获取模板表格数据失败：' . $e->getMessage(), [
                'params' => $params,
                'plugin' => 'TencentSmsPlugin'
            ]);

            return [
                'data' => [],
                'total' => 0,
                'page' => 1,
                'page_size' => 20,
                'total_pages' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 执行模板表格操作
     */
    protected function executeTemplatesTableAction(string $action, array $params = []): array
    {
        try {
            switch ($action) {
                case 'sync_templates':
                    return $this->syncTemplates();

                case 'batch_enable':
                    $recordIds = $params['record_ids'] ?? [];
                    return $this->batchTemplatesTableOperation('enable', $recordIds);

                case 'batch_disable':
                    $recordIds = $params['record_ids'] ?? [];
                    return $this->batchTemplatesTableOperation('disable', $recordIds);

                default:
                    return ['success' => false, 'message' => '未知操作'];
            }
        } catch (\Exception $e) {
            \Log::error('执行模板表格操作失败：' . $e->getMessage(), [
                'action' => $action,
                'params' => $params,
                'plugin' => 'TencentSmsPlugin'
            ]);

            return ['success' => false, 'message' => '操作失败：' . $e->getMessage()];
        }
    }

    /**
     * 更新模板表格记录
     */
    protected function updateTemplatesTableRecord($recordId, array $data): array
    {
        try {
            $template = \Plugins\TencentSmsPlugin\Models\TencentSmsTemplate::find($recordId);

            if (!$template) {
                return ['success' => false, 'message' => '模板不存在'];
            }

            // 只允许更新状态字段
            $allowedFields = ['status'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));

            if (empty($updateData)) {
                return ['success' => false, 'message' => '没有可更新的字段'];
            }

            $template->update($updateData);

            return [
                'success' => true,
                'message' => '更新成功',
                'data' => $template
            ];
        } catch (\Exception $e) {
            \Log::error('更新模板表格记录失败：' . $e->getMessage(), [
                'record_id' => $recordId,
                'data' => $data,
                'plugin' => 'TencentSmsPlugin'
            ]);

            return ['success' => false, 'message' => '更新失败：' . $e->getMessage()];
        }
    }

    /**
     * 删除模板表格记录
     */
    protected function deleteTemplatesTableRecord($recordId): array
    {
        try {
            $template = \Plugins\TencentSmsPlugin\Models\TencentSmsTemplate::find($recordId);

            if (!$template) {
                return ['success' => false, 'message' => '模板不存在'];
            }

            $template->delete();

            return ['success' => true, 'message' => '删除成功'];
        } catch (\Exception $e) {
            \Log::error('删除模板表格记录失败：' . $e->getMessage(), [
                'record_id' => $recordId,
                'plugin' => 'TencentSmsPlugin'
            ]);

            return ['success' => false, 'message' => '删除失败：' . $e->getMessage()];
        }
    }

    /**
     * 批量操作模板表格记录
     */
    protected function batchTemplatesTableOperation(string $operation, array $recordIds): array
    {
        try {
            if (empty($recordIds)) {
                return ['success' => false, 'message' => '请选择要操作的记录'];
            }

            switch ($operation) {
                case 'enable':
                    $count = \Plugins\TencentSmsPlugin\Models\TencentSmsTemplate::whereIn('id', $recordIds)
                        ->update(['status' => true]);
                    return [
                        'success' => true,
                        'message' => "成功启用 {$count} 个模板",
                        'affected_count' => $count
                    ];

                case 'disable':
                    $count = \Plugins\TencentSmsPlugin\Models\TencentSmsTemplate::whereIn('id', $recordIds)
                        ->update(['status' => false]);
                    return [
                        'success' => true,
                        'message' => "成功禁用 {$count} 个模板",
                        'affected_count' => $count
                    ];

                case 'delete':
                    $count = \Plugins\TencentSmsPlugin\Models\TencentSmsTemplate::whereIn('id', $recordIds)
                        ->delete();
                    return [
                        'success' => true,
                        'message' => "成功删除 {$count} 个模板",
                        'affected_count' => $count
                    ];

                default:
                    return ['success' => false, 'message' => '未知批量操作'];
            }
        } catch (\Exception $e) {
            \Log::error('批量操作模板表格记录失败：' . $e->getMessage(), [
                'operation' => $operation,
                'record_ids' => $recordIds,
                'plugin' => 'TencentSmsPlugin'
            ]);

            return ['success' => false, 'message' => '批量操作失败：' . $e->getMessage()];
        }
    }

    /**
     * 同步模板（兼容旧接口）
     */
    protected function syncTemplates(): array
    {
        try {
            $syncService = app(\Plugins\TencentSmsPlugin\Services\TencentTemplateSyncService::class);
            $result = $syncService->syncTemplates();

            return [
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '同步失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取插件配置定义
     */
    public function getConfigSchema(): array
    {
        return [
            'fields' => [
                'TENCENT_SMS_SECRET_ID' => [
                    'type' => 'password',
                    'label' => 'SecretId',
                    'description' => '腾讯云SecretId',
                    'placeholder' => '请输入SecretId',
                    'required' => true,
                    'validation' => 'required|string|min:10|max:64',
                    'group' => 'config',
                    'order' => 1
                ],
                'TENCENT_SMS_SECRET_KEY' => [
                    'type' => 'password',
                    'label' => 'SecretKey',
                    'description' => '腾讯云SecretKey',
                    'placeholder' => '请输入SecretKey',
                    'required' => true,
                    'validation' => 'required|string|min:10|max:64',
                    'group' => 'config',
                    'order' => 2
                ],
                'TENCENT_SMS_SDK_APP_ID' => [
                    'type' => 'text',
                    'label' => 'SDK AppId',
                    'description' => '腾讯云短信SDK AppId',
                    'placeholder' => '请输入SDK AppId',
                    'required' => true,
                    'validation' => 'required|string|min:8|max:20',
                    'group' => 'config',
                    'order' => 3
                ],
                'TENCENT_SMS_SIGN_NAME' => [
                    'type' => 'text',
                    'label' => '短信签名',
                    'description' => '短信签名名称',
                    'placeholder' => '请输入短信签名',
                    'required' => true,
                    'validation' => 'required|string|max:100',
                    'group' => 'config',
                    'order' => 4
                ],
                'TENCENT_SMS_REGION_ID' => [
                    'type' => 'select',
                    'label' => '地域节点',
                    'description' => '腾讯云地域节点',
                    'options' => [
                        ['value' => 'ap-guangzhou', 'label' => '广州'],
                        ['value' => 'ap-beijing', 'label' => '北京'],
                        ['value' => 'ap-shanghai', 'label' => '上海'],
                        ['value' => 'ap-chengdu', 'label' => '成都'],
                        ['value' => 'ap-chongqing', 'label' => '重庆']
                    ],
                    'default' => 'ap-guangzhou',
                    'group' => 'config',
                    'order' => 5
                ],
                'TENCENT_SMS_TEMPLATE_SYNC_BTN' => [
                    'type' => 'button',
                    'label' => '同步模板',
                    'description' => '从腾讯云同步短信模板到本地数据库',
                    'action' => 'sync_templates',
                    'variant' => 'primary',
                    'confirm' => '确定要从腾讯云同步短信模板吗？此操作可能需要一些时间。',
                    'group' => 'templates',
                    'order' => 1
                ],
                'TENCENT_SMS_TEMPLATES' => [
                    'type' => 'data_table',
                    'label' => '短信模板列表',
                    'description' => '腾讯云短信模板列表，支持增删改查和状态管理',
                    'data_source' => [
                        'type' => 'api',
                        'url' => '/api/admin/plugins/TencentSmsPlugin/config/data-tables/TENCENT_SMS_TEMPLATES',
                        'method' => 'GET'
                    ],
                    'columns' => [
                        [
                            'key' => 'template_id',
                            'label' => '模板ID',
                            'type' => 'text',
                            'width' => '120px',
                            'sortable' => true,
                            'searchable' => true
                        ],
                        [
                            'key' => 'template_name',
                            'label' => '模板名称',
                            'type' => 'text',
                            'width' => '150px',
                            'sortable' => true,
                            'searchable' => true
                        ],
                        [
                            'key' => 'template_content',
                            'label' => '模板内容',
                            'type' => 'textarea',
                            'width' => '300px',
                            'searchable' => true
                        ],
                        [
                            'key' => 'audit_status',
                            'label' => '审核状态',
                            'type' => 'badge',
                            'width' => '100px',
                            'sortable' => true,
                            'options' => [
                                ['value' => 'pending', 'label' => '待审核', 'color' => 'warning'],
                                ['value' => 'approved', 'label' => '已通过', 'color' => 'success'],
                                ['value' => 'rejected', 'label' => '已拒绝', 'color' => 'danger']
                            ]
                        ],
                        [
                            'key' => 'international',
                            'label' => '国际模板',
                            'type' => 'boolean',
                            'width' => '100px',
                            'sortable' => true
                        ],
                        [
                            'key' => 'status',
                            'label' => '启用状态',
                            'type' => 'switch',
                            'width' => '100px',
                            'editable' => true,
                            'sortable' => true
                        ],
                        [
                            'key' => 'updated_at',
                            'label' => '更新时间',
                            'type' => 'datetime',
                            'width' => '160px',
                            'sortable' => true
                        ]
                    ],
                    'actions' => [
                        [
                            'type' => 'button',
                            'label' => '刷新',
                            'action' => 'refresh',
                            'icon' => 'refresh',
                            'variant' => 'outline-secondary'
                        ]
                    ],
                    'operations' => [
                        'create' => false,
                        'update' => true,
                        'delete' => true,
                        'batch_delete' => true,
                        'export' => true
                    ],
                    'pagination' => [
                        'default_page_size' => 20,
                        'page_size_options' => [10, 20, 50, 100],
                        'show_quick_jumper' => true,
                        'show_size_changer' => true,
                        'show_total' => true
                    ],
                    'default_sort' => [
                        'field' => 'updated_at',
                        'order' => 'desc'
                    ],
                    'group' => 'templates',
                    'order' => 2
                ]
            ],
            'groups' => [
                'config' => [
                    'label' => '配置分组',
                    'description' => '腾讯云短信服务配置',
                    'icon' => 'settings',
                    'order' => 1
                ],
                'templates' => [
                    'label' => '短信模版',
                    'description' => '腾讯云短信模板列表展示和状态管理',
                    'icon' => 'list',
                    'order' => 2
                ]
            ],
            'values' => [
                'TENCENT_SMS_SECRET_ID' => '',
                'TENCENT_SMS_SECRET_KEY' => '',
                'TENCENT_SMS_SDK_APP_ID' => '',
                'TENCENT_SMS_SIGN_NAME' => '',
                'TENCENT_SMS_REGION_ID' => 'ap-guangzhou'
            ]
        ];
    }
}
