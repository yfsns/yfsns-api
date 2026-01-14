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

namespace App\Modules\Comment\DTOs;

use App\Modules\Comment\Enums\CommentSort;
use App\Modules\Comment\Enums\CommentTargetType;

readonly class CommentListQuery
{
    public function __construct(
        public CommentTargetType $targetType,
        public int $targetId,
        public ?int $cursor = null,
        public int $limit = 10,
        public CommentSort $sort = CommentSort::LATEST,
    ) {
    }

    /**
     * 从数组创建查询对象
     */
    public static function fromArray(array $params): self
    {
        $targetType = CommentTargetType::tryFrom($params['target_type'] ?? 'post') 
            ?? CommentTargetType::POST;

        return new self(
            targetType: $targetType,
            targetId: (int) ($params['target_id'] ?? 0),
            cursor: isset($params['cursor']) ? (int) $params['cursor'] : null,
            limit: (int) ($params['limit'] ?? 10),
            sort: CommentSort::fromString($params['sort'] ?? 'latest'),
        );
    }
}

