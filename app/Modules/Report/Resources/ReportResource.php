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

namespace App\Modules\Report\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
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
            'reportableType' => $this->reportable_type,
            'reportableId' => $this->reportable_id,
            'type' => $this->type,
            'content' => $this->content,
            'description' => $this->description,
            'evidence' => $this->evidence,
            'status' => $this->status,
            'result' => $this->result,
            'handlerId' => $this->handler_id,
            'handledAt' => $this->handled_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
            // 关联数据
            'reporter' => $this->whenLoaded('reporter', function () {
                return [
                    'id' => $this->reporter->id,
                    'username' => $this->reporter->username,
                    'nickname' => $this->reporter->nickname,
                    'avatar' => $this->reporter->avatar,
                ];
            }),
            'reportable' => $this->whenLoaded('reportable', function () {
                return [
                    'id' => $this->reportable->id,
                    'type' => $this->reportable_type,
                    'content' => $this->reportable->content ?? $this->reportable->title ?? '未知内容',
                ];
            }),
        ];
    }
}
