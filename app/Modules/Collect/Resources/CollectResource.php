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

namespace App\Modules\Collect\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CollectResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'collectableId' => $this->collectable_id,
            'collectableType' => $this->collectable_type,
            'type' => $this->type,
            'remark' => $this->remark,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,

            // 关联数据
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => (string) $this->user->id,
                    'nickname' => $this->user->nickname,
                    'avatarUrl' => $this->user->avatar_url ?? null,
                ];
            }),

            'collectable' => $this->whenLoaded('collectable', function () {
                return [
                    'id' => $this->collectable->id,
                    'title' => $this->collectable->title ?? $this->collectable->content ?? null,
                    'type' => class_basename($this->collectable),
                ];
            }),
        ];
    }
}
