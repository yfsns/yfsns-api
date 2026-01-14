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

namespace App\Modules\Notification\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NotificationCursorResource extends ResourceCollection
{
    protected $hasMore;

    protected $nextCursor;

    protected $limit;

    protected $paginationType;

    public function __construct($resource, $hasMore = false, $nextCursor = null, $limit = 15, $paginationType = 'cursor')
    {
        parent::__construct($resource);
        $this->hasMore = $hasMore;
        $this->nextCursor = $nextCursor;
        $this->limit = $limit;
        $this->paginationType = $paginationType;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'hasMore' => $this->hasMore,
            'nextCursor' => $this->nextCursor,
            'limit' => $this->limit,
            'paginationType' => $this->paginationType,
        ];
    }
}
