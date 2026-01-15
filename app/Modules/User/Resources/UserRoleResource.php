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

use App\Modules\User\Models\UserRole;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRoleResource extends JsonResource
{

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'key' => $this->key,
            'description' => $this->description,
            'type' => (int) $this->type,
            'typeText' => $this->type_text,
            'status' => (int) $this->status,
            'statusText' => $this->status_text,
            'sort' => (int) $this->sort,
            'isSystem' => (bool) $this->is_system,
            'isSystemPreset' => $this->isSystemPresetRole(),
            'isPaidRole' => $this->type === UserRole::TYPE_PREMIUM,
            'isDefault' => (bool) $this->is_default,
            'canDelete' => $this->canBeDeleted(),
            'isSystemText' => $this->is_system ? '系统角色' : '自定义角色',
            'isSystemPresetText' => $this->isSystemPresetRole() ? '内置' : '可配置',
            'isPaidRoleText' => $this->type === UserRole::TYPE_PREMIUM ? '付费角色' : '普通角色',
            'isDefaultText' => $this->is_default ? '默认角色' : '非默认角色',
            'canDeleteText' => $this->canBeDeleted() ? '可删除' : '不可删除',
            'permissions' => $this->getPermissions(),
            'userCount' => (int) ($this->users_count ?? 0),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
