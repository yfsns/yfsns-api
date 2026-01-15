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

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 头像上传结果资源类
 */
class AvatarUploadResource extends JsonResource
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
        $status = $this->resource['status'] ?? 'pending';
        
        return [
            'assetId' => $this->resource['asset_id'] ?? null,
            'status' => $status,
            'statusText' => $this->getStatusText($status),
            'message' => $this->resource['message'] ?? '',
            'canUpload' => $this->resource['can_upload'] ?? false,
            'reviewStatus' => $this->resource['review_status'] ?? 'pending',
            'pendingAvatarUrl' => $this->resource['pending_avatar_url'] ?? null,
        ];
    }

    /**
     * 获取状态文本.
     */
    protected function getStatusText(string $status): string
    {
        return match ($status) {
            'pending' => '待审核',
            'approved' => '已通过',
            'rejected' => '已拒绝',
            default => '未知',
        };
    }
}
