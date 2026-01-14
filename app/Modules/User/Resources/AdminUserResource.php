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

use App\Modules\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{

    /**
     * 标准化查询参数
     * 将前端驼峰格式转换为后端下划线格式
     *
     * @param array $params 前端传入的参数
     * @return array 标准化后的参数
     */
    public static function normalizeQueryParams(array $params): array
    {
        return [
            'keyword' => $params['keyword'] ?? null,
            'status' => $params['status'] ?? null,
            'role_id' => $params['roleId'] ?? $params['role_id'] ?? null,
            'sort_field' => $params['sortField'] ?? $params['sort_field'] ?? 'id',
            'sort_order' => $params['sortOrder'] ?? $params['sort_order'] ?? 'desc',
            'per_page' => $params['perPage'] ?? $params['per_page'] ?? 15,
            'page' => $params['page'] ?? 1,
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'username' => $this->username,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'statusText' => $this->getStatusText(),
            // 关联角色信息
            'role' => $this->whenLoaded('role', function () {
                return [
                    'id' => $this->role->id,
                    'roleName' => $this->role->name,
                    'roleKey' => $this->role->key,
                    'isDefault' => (bool) $this->role->is_default,
                ];
            }),
            // 时间字段 - 驼峰格式
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 获取状态文本
     */
    private function getStatusText(): string
    {
        return match ((int) $this->status) {
            User::STATUS_ENABLED => '正常',
            User::STATUS_DISABLED => '禁用',
            default => '未知',
        };
    }
}
