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

namespace App\Modules\Review\Services;

use App\Modules\Review\Events\ContentPendingAudit;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * 审核决策服务
 *
 * 负责内容审核流程的决策逻辑：
 * - AI审核插件启用：等待AI审核处理
 * - AI审核插件未启用：保持待审核状态，等待人工审核
 */
class ReviewDecisionService
{
    /**
     * 处理内容审核决策.
     *
     * @param ContentPendingAudit $event 待审核事件
     * @return array 处理结果 ['action' => string, 'message' => string]
     */
    public function processDecision(ContentPendingAudit $event): array
    {
        try {
            $model = $this->resolveContentModel($event);

            if (!$model) {
                Log::warning('审核决策服务：未找到对应内容', [
                    'content_type' => $event->contentType,
                    'content_id' => $event->contentId,
                ]);

                return [
                    'action' => 'skip',
                    'message' => '内容不存在'
                ];
            }

            return $this->makeDecision($model, $event);
        } catch (Exception $e) {
            Log::error('审核决策服务：处理决策失败', [
                'error' => $e->getMessage(),
                'content_type' => $event->contentType,
                'content_id' => $event->contentId,
            ]);

            return [
                'action' => 'error',
                'message' => '决策处理失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 执行审核决策.
     *
     * @param mixed $model 内容模型
     * @param ContentPendingAudit $event 待审核事件
     * @return array 决策结果
     */
    protected function makeDecision($model, ContentPendingAudit $event): array
    {
        if ($this->isAiAuditEnabled()) {
            // AI审核启用：等待AI审核
            return [
                'action' => 'wait_for_ai',
                'message' => 'AI审核插件已启用，等待AI审核处理'
            ];
        }

        // AI审核未启用：等待人工审核
        return [
            'action' => 'wait_for_manual',
            'message' => 'AI审核插件未启用，等待人工审核'
        ];
    }


    /**
     * 检查AI审核是否启用.
     *
     * @return bool
     */
    protected function isAiAuditEnabled(): bool
    {
        try {
            $pluginManager = app('plugin.manager');

            if (!$pluginManager) {
                Log::debug('AI审核检查：插件管理器不可用');
                return false;
            }

            // 检查ContentAudit插件是否启用
            if (!$pluginManager->isPluginEnabled('ContentAudit')) {
                Log::debug('AI审核检查：ContentAudit插件未启用');
                return false;
            }

            // 检查ContentAudit插件内部的AUDIT_ENABLED配置
            $config = $pluginManager->getPluginConfig('ContentAudit');
            $isEnabled = $config && isset($config['AUDIT_ENABLED']) && $config['AUDIT_ENABLED'];

            Log::debug('AI审核检查结果', [
                'plugin_enabled' => true,
                'audit_enabled' => $isEnabled,
            ]);

            return $isEnabled;
        } catch (Exception $e) {
            Log::warning('AI审核启用状态检查失败', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }


    /**
     * 根据类型获取模型实例.
     *
     * @param ContentPendingAudit $event 待审核事件
     * @return mixed
     */
    protected function resolveContentModel(ContentPendingAudit $event)
    {
        return match ($event->contentType) {
            'post' => \App\Modules\Post\Models\Post::find($event->contentId),
            'comment' => \App\Modules\Comment\Models\Comment::find($event->contentId),
            'topic' => \App\Modules\Topic\Models\Topic::find($event->contentId),
            default => null,
        };
    }
}
