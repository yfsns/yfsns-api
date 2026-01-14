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

namespace App\Modules\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMentionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender' => $this->whenLoaded('sender', function () {
                return [
                    'id' => $this->sender->id,
                    'username' => $this->sender->username,
                    'nickname' => $this->sender->nickname,
                    'avatar_url' => $this->sender->avatar ? config('app.url') . '/storage/' . $this->sender->avatar : config('app.url') . '/assets/default_avatars.png',
                ];
            }),
            'receiver' => $this->whenLoaded('receiver', function () {
                return [
                    'id' => $this->receiver->id,
                    'username' => $this->receiver->username,
                    'nickname' => $this->receiver->nickname,
                    'avatar_url' => $this->receiver->avatar ? config('app.url') . '/storage/' . $this->receiver->avatar : config('app.url') . '/assets/default_avatars.png',
                ];
            }),
            'content_type' => $this->content_type,
            'content_type_text' => $this->content_type_text,
            'content_id' => $this->content_id,
            'username' => $this->username,
            'nickname_at_time' => $this->nickname_at_time,
            'position' => $this->position,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'read_at' => $this->read_at,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
