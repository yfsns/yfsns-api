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

namespace App\Modules\SensitiveWord\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SensitiveWordLogResource extends JsonResource
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
            'sensitiveWordId' => $this->sensitive_word_id,
            'contentType' => $this->content_type,
            'contentId' => $this->content_id,
            'userId' => $this->user_id,
            'originalContent' => $this->original_content,
            'filteredContent' => $this->filtered_content,
            'action' => $this->action,
            'ip' => $this->ip,

            // 关联数据
            'sensitiveWord' => $this->whenLoaded('sensitiveWord', function () {
                return [
                    'id' => $this->sensitiveWord->id,
                    'word' => $this->sensitiveWord->word,
                    'category' => $this->sensitiveWord->category,
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'username' => $this->user->username,
                    'nickname' => $this->user->nickname,
                ];
            }),

            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}

