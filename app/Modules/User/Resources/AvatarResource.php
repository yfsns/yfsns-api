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

use App\Modules\User\Models\UserAsset;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 头像资源类 - 支持驼峰命名
 */
class AvatarResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'currentAvatar' => $this->resource['current_avatar'],
            'reviewStatus' => $this->resource['review_status'],
            'pendingAvatar' => $this->resource['pending_asset'] ? [
                'id' => $this->resource['pending_asset']['id'],
                'url' => $this->resource['pending_asset']['url'],
                'uploadedAt' => $this->resource['pending_asset']['submitted_at'],
            ] : null,
        ];
    }
}


