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

namespace App\Modules\Topic\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 话题更新事件（通用）
 *
 * 当任何内容的话题标签发生变化时触发此事件
 * 支持所有内容类型：post, comment, article, forum_thread等
 */
class TopicsUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * 内容类型：post, comment, article, forum_thread等
     */
    public string $contentType;

    /**
     * 内容ID
     */
    public int $contentId;

    /**
     * 话题ID数组
     */
    public array $topicIds;

    /**
     * 操作类型：sync, attach, detach
     */
    public string $action;

    /**
     * 创建事件实例
     *
     * @param string $contentType 内容类型
     * @param int    $contentId   内容ID
     * @param array  $topicIds    话题ID数组
     * @param string $action      操作类型
     */
    public function __construct(
        string $contentType,
        int $contentId,
        array $topicIds,
        string $action = 'sync'
    ) {
        $this->contentType = $contentType;
        $this->contentId = $contentId;
        $this->topicIds = $topicIds;
        $this->action = $action;
    }
}
