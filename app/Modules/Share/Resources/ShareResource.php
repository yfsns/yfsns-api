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

namespace App\Modules\Share\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShareResource extends JsonResource
{

    /**
     * 转换资源为数组.
     */
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'userId' => (string) $this->user_id,
            'shareableId' => (string) $this->shareable_id,
            'shareableType' => $this->shareable_type,
            'type' => $this->type,
            'platform' => $this->platform,
            'url' => $this->url,
            'ip' => $this->ip,
            'device' => $this->device,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
            // 关联数据
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => (string) $this->user->id,
                    'username' => $this->user->username ?? '',
                    'nickname' => $this->user->nickname ?? '',
                    'avatar' => $this->user->avatar_url ?? '',
                    'avatarUrl' => $this->user->avatar_url ?? '',
                ];
            }),
            'shareable' => $this->whenLoaded('shareable', function () {
                return $this->shareable;
            }),
        ];
    }
}

