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

namespace App\Modules\Post\Resources;

class VideoResource extends BasePostResource
{
    protected function getType(): string
    {
        return 'video';
    }

    protected function getSpecificFields($request): array
    {
        return [
            'videos' => $this->whenLoaded('files', function () {
                return $this->files->filter(function ($file) {
                    return $file->type === 'video';
                })->map(function ($file) {
                    return [
                        'fileId' => $file->id,
                        'name' => $file->name,
                        'url' => $file->url, // 使用File模型的url属性获取完整URL
                        'size' => $file->size,
                        'mimeType' => $file->mime_type,
                        'thumbnail' => $file->thumbnail,
                        'duration' => $file->duration ?? $this->getVideoDuration($file),
                        'resolution' => $this->getVideoResolution($file),
                        'bitrate' => $file->bitrate ?? null,
                        'quality' => $this->getVideoQuality($file),
                    ];
                });
            }),
            'coverImage' => $this->whenLoaded('files', function () {
                $cover = $this->files->first(function ($file) {
                    return $file->type === \App\Modules\File\Models\File::TYPE_COVER;
                });
                return $cover ? [
                    'fileId' => $cover->id,
                    'name' => $cover->name,
                    'url' => $cover->url, // 使用File模型的url属性获取完整URL
                    'size' => $cover->size,
                    'mimeType' => $cover->mime_type,
                ] : null;
            }),
            'videoInfo' => [
                'totalDuration' => $this->getTotalVideoDuration(),
                'totalSize' => $this->getTotalVideoSize(),
                'hasMultipleQuality' => $this->hasMultipleQuality(),
                'availableQualities' => $this->getAvailableQualities(),
            ],
        ];
    }

    /**
     * 获取视频时长
     */
    protected function getVideoDuration($file): ?int
    {
        return $file->metadata['duration'] ?? null;
    }

    /**
     * 获取视频分辨率
     */
    protected function getVideoResolution($file): ?string
    {
        return $file->metadata['resolution'] ?? null;
    }

    /**
     * 获取视频质量
     */
    protected function getVideoQuality($file): string
    {
        $bitrate = $file->bitrate ?? 0;

        if ($bitrate >= 8000000) return '4K';
        if ($bitrate >= 5000000) return '1080p';
        if ($bitrate >= 2500000) return '720p';
        if ($bitrate >= 1000000) return '480p';

        return 'SD';
    }

    /**
     * 获取总视频时长
     */
    protected function getTotalVideoDuration(): ?int
    {
        if (!$this->relationLoaded('files')) {
            return null;
        }

        return $this->files->where('type', 'video')->sum(function ($file) {
            return $file->metadata['duration'] ?? 0;
        });
    }

    /**
     * 获取总视频大小
     */
    protected function getTotalVideoSize(): int
    {
        if (!$this->relationLoaded('files')) {
            return 0;
        }

        return $this->files->where('type', 'video')->sum('size');
    }

    /**
     * 是否有多质量版本
     */
    protected function hasMultipleQuality(): bool
    {
        if (!$this->relationLoaded('files')) {
            return false;
        }

        $qualities = $this->files->where('type', 'video')
            ->map(function ($file) {
                return $this->getVideoQuality($file);
            })
            ->unique()
            ->count();

        return $qualities > 1;
    }

    /**
     * 获取可用的质量列表
     */
    protected function getAvailableQualities(): array
    {
        if (!$this->relationLoaded('files')) {
            return [];
        }

        return $this->files->where('type', 'video')
            ->map(function ($file) {
                return $this->getVideoQuality($file);
            })
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }
}