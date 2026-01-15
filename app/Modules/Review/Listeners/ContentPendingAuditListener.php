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

namespace App\Modules\Review\Listeners;

use App\Modules\Review\Events\ContentPendingAudit;
use App\Modules\Review\Services\ReviewDecisionService;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * 内容待审核事件监听器.
 *
 * 监听ContentPendingAudit事件，调用审核决策服务处理审核逻辑。
 * 监听器只负责事件监听和分发，不包含业务逻辑。
 */
class ContentPendingAuditListener
{
    /**
     * 处理内容待审核事件.
     *
     * 只负责监听事件并调用审核决策服务，不包含任何业务逻辑。
     */
    public function handle(ContentPendingAudit $event): void
    {
        Log::info('Review 监听器：监听到内容待审核事件', [
            'content_type' => $event->contentType,
            'content_id' => $event->contentId,
        ]);

        try {
            $decisionService = app(ReviewDecisionService::class);
            $result = $decisionService->processDecision($event);

            Log::info('Review 监听器：审核决策处理完成', [
                'content_type' => $event->contentType,
                'content_id' => $event->contentId,
                'action' => $result['action'],
                'message' => $result['message'],
            ]);
        } catch (Exception $e) {
            Log::error('Review 监听器：调用审核决策服务失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'content_type' => $event->contentType,
                'content_id' => $event->contentId,
            ]);
        }
    }
}
