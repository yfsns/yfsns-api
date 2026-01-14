<?php

namespace App\Modules\Review\Events;

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

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 内容待审核事件.
 *
 * 当内容状态变为待审核时触发此事件
 * 插件可以监听此事件，主动获取待审核任务
 */
class ContentPendingAudit
{
    use Dispatchable, SerializesModels;

    /**
     * 内容类型（article、post、thread、comment）.
     */
    public string $contentType;

    /**
     * 内容ID.
     */
    public int $contentId;

    /**
     * 创建事件实例.
     */
    public function __construct(string $contentType, int $contentId)
    {
        $this->contentType = $contentType;
        $this->contentId = $contentId;
    }
}
