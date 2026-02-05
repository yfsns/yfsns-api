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

class ArticleResource extends BasePostResource
{
    protected function getType(): string
    {
        return 'article';
    }

    protected function getSpecificFields($request): array
    {
        return [
            'excerpt' => \Illuminate\Support\Str::limit(strip_tags($this->content_html ?? ''), 200), // 文章摘要
            'readingTime' => $this->estimateReadingTime(), // 预估阅读时间（分钟）
            'wordCount' => $this->getWordCount(), // 字数统计
            'images' => $this->whenLoaded('files', function () {
                return $this->files->filter(function ($file) {
                    return $file->type === \App\Modules\File\Models\File::TYPE_IMAGE;
                })->map(function ($file) {
                    return [
                        'fileId' => $file->id,
                        'name' => $file->name,
                        'url' => $file->url, // 使用File模型的url属性获取完整URL
                        'size' => $file->size,
                        'mimeType' => $file->mime_type,
                    ];
                });
            }),
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
            'documents' => $this->whenLoaded('files', function () {
                return $this->files->filter(function ($file) {
                    return $file->type === 'document';
                })->map(function ($file) {
                    return [
                        'fileId' => $file->id,
                        'name' => $file->name,
                        'url' => $file->url, // 使用File模型的url属性获取完整URL
                        'size' => $file->size,
                        'mimeType' => $file->mime_type,
                    ];
                });
            }),
        ];
    }

    /**
     * 预估阅读时间（分钟）
     */
    protected function estimateReadingTime(): int
    {
        $wordCount = $this->getWordCount();
        $wordsPerMinute = 300; // 中文阅读速度
        return max(1, ceil($wordCount / $wordsPerMinute));
    }

    /**
     * 获取字数
     */
    protected function getWordCount(): int
    {
        return mb_strlen(strip_tags($this->content_html ?? ''));
    }
}