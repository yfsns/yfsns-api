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

namespace App\Modules\User\Listeners;

use App\Modules\User\Events\UserMentionsCreated;
use App\Modules\User\Services\UserMentionService;
use Illuminate\Support\Facades\Log;

/**
 * 用户提及创建事件监听器（通用）
 *
 * 监听UserMentionsCreated事件，处理所有内容类型的用户提及关联业务逻辑
 * 支持：post, comment, article, forum_thread等任何内容类型
 */
class UserMentionsCreatedListener
{
    /**
     * 处理用户提及创建事件
     *
     * @param UserMentionsCreated $event 用户提及创建事件
     */
    public function handle(UserMentionsCreated $event): void
    {
        try {
            $mentionService = app(UserMentionService::class);

            $count = $mentionService->createMentions(
                $event->senderId,
                $event->receiverIds,
                $event->contentType,
                $event->contentId
            );

            if ($count > 0) {
                Log::info('用户提及创建成功', [
                    'sender_id' => $event->senderId,
                    'receiver_ids' => $event->receiverIds,
                    'content_type' => $event->contentType,
                    'content_id' => $event->contentId,
                    'mentions_count' => $count,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('用户提及创建失败', [
                'sender_id' => $event->senderId,
                'receiver_ids' => $event->receiverIds,
                'content_type' => $event->contentType,
                'content_id' => $event->contentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 重新抛出异常，让事件系统处理
            throw $e;
        }
    }
}
