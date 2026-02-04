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

class UserResource extends JsonResource
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
        $genderMap = [1 => '男', 2 => '女', 0 => '保密'];
        $data = [
            'id' => (string) $this->id,
            'username' => $this->username,
            'nickname' => $this->nickname,
            'avatarUrl' => $this->avatar_url,
            'gender' => $genderMap[(int) $this->gender] ?? '保密',
            'bio' => $this->bio,
            'role' => $this->whenLoaded('role', function () {
                return [
                    'id' => $this->role?->id,
                    'key' => $this->role?->key,
                    'name' => $this->role?->name,
                    'isDefault' => (bool) $this->role?->is_default,
                ];
            }),
            'status' => $this->status,
            'statusText' => match ((int) $this->status) {
                User::STATUS_ENABLED => '正常',
                User::STATUS_DISABLED => '禁用',
                default => '未知',
            },
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];

        // 支持withCount的统计字段
        if (isset($this->followers_count)) {
            $data['followers'] = $this->followers_count;
        }
        if (isset($this->following_count)) {
            $data['following'] = $this->following_count;
        }
        if (isset($this->posts_count)) {
            $data['posts'] = $this->posts_count;
        }
        if (isset($this->collects_count)) {
            $data['collects'] = $this->collects_count;
        }


        return $data;
    }
}
