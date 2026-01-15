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

namespace App\Modules\Auth\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

    /**
     * 转换资源为数组.
     */
    public function toArray($request): array
    {
        // 简化实现，完全移除头像相关代码以避免耦合
        $statusText = $this->getStatusText();

        return [
            'id' => (string) $this->resource->id,
            'username' => $this->resource->username,
            'nickname' => $this->resource->nickname,
            'avatarUrl' => $this->resource->avatar ? config('app.url') . '/storage/' . $this->resource->avatar : config('app.url') . '/assets/default_avatars.png',
        ];
    }


    /**
     * 获取状态文本.
     */
    protected function getStatusText(): string
    {
        return match ((int) $this->status) {
            1 => '正常',
            0 => '禁用',
            default => '未知',
        };
    }
}
