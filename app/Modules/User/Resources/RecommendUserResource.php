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

class RecommendUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $genderMap = [1 => '男', 2 => '女', 0 => '保密'];

        return [
            // 基础信息
            'id' => (string) $this->id,
            'username' => $this->username,
            'nickname' => $this->nickname,
            'avatarUrl' => $this->avatar ? config('app.url') . '/storage/' . $this->avatar : config('app.url') . '/assets/default_avatars.png',

            // 公开的个人资料
            'gender' => $genderMap[(int) $this->gender] ?? '保密',
            'bio' => $this->bio,

        ];
    }
}
