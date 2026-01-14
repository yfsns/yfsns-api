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

namespace App\Modules\File\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{

    public function toArray($request)
    {
        $data = [
            'fileId' => (string) $this->id,
            'name' => $this->name,
            //'path' => $this->path,
            'url' => $this->url,
            'size' => $this->size,
            'mimeType' => $this->mime_type,
            'type' => $this->type,
            //'module' => $this->module,
            //'moduleId' => $this->module_id ? (string) $this->module_id : null,
            //'storage' => $this->storage,
            'userId' => $this->user_id ? (string) $this->user_id : null,
            //'createdAt' => $this->created_at?->toISOString(),
            //'updatedAt' => $this->updated_at?->toISOString(),
        ];

        // 媒体文件额外信息
        if ($this->type === 'image') {
            $data['thumbnail'] = $this->thumbnail_url;
            $data['thumbnails'] = $this->thumbnails ?? [];
        } elseif ($this->type === 'video') {
            $data['cover'] = $this->video_cover_url;
            $data['duration'] = $this->duration;
            $data['videoId'] = $this->video_id;
        }

        // 维度信息（图片和视频都可能有）
            if ($this->width && $this->height) {
                $data['width'] = $this->width;
                $data['height'] = $this->height;
        }

        return $data;
    }

}
