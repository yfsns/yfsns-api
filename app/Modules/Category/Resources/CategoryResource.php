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

namespace App\Modules\Category\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
        $data = [
            'id' => (string) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'color' => $this->color,
            'parentId' => $this->parent_id ? (string) $this->parent_id : null,
            'sortOrder' => $this->sort_order,
            'isActive' => $this->is_active,
            'isSystem' => $this->is_system,
            'metadata' => $this->metadata,
            'depth' => $this->depth, // 访问器
            'path' => $this->path, // 访问器
            'url' => $this->url, // 访问器
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];

        // 关联数据（按需加载）
        $this->mergeWhen($this->relationLoaded('parent'), [
            'parent' => $this->parent ? [
                'id' => (string) $this->parent->id,
                'name' => $this->parent->name,
                'slug' => $this->parent->slug,
            ] : null,
        ]);

        $this->mergeWhen($this->relationLoaded('children'), [
            'children' => CategoryResource::collection($this->children),
            'childrenCount' => $this->children_count, // 访问器
        ]);

        // 统计数据
        $this->mergeWhen($this->relationLoaded('categorizables'), [
            'contentCount' => $this->content_count, // 访问器
        ]);

        return $data;
    }
}