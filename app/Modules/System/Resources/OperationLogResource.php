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

namespace App\Modules\System\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use function is_string;

class OperationLogResource extends JsonResource
{
    public function toArray($request): array
    {
        $createdAt = $this->created_at;
        $updatedAt = $this->updated_at;

        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'username' => $this->user->username,
                    'nickname' => $this->user->nickname,
                    'avatar' => $this->user->avatar_url ?? null,
                ];
            }),
            'module' => $this->module,
            'action' => $this->action,
            'description' => $this->description,
            'method' => $this->method,
            'url' => $this->url,
            'params' => is_string($this->params) ? json_decode($this->params, true) : ($this->params ?? []),
            'ip' => $this->ip,
            'userAgent' => $this->user_agent,
            'createdAt' => $createdAt?->format('Y-m-d H:i:s'),
            'createdAtISO' => $createdAt?->toISOString(),
            'createdAtHuman' => $createdAt?->diffForHumans(),
            'updatedAt' => $updatedAt?->format('Y-m-d H:i:s'),
            'updatedAtISO' => $updatedAt?->toISOString(),
        ];
    }
}
