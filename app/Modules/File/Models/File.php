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

namespace App\Modules\File\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use SoftDeletes;

    /**
     * 文件类型常量
     */
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_AUDIO = 'audio';
    public const TYPE_COVER = 'cover';

    protected $fillable = [
        'name',
        'original_name',
        'path',
        'size',
        'mime_type',
        'type',
        'storage',
        'module',
        'module_id',
        'user_id',
        'status',
        'thumbnail',
        'duration',
        'width',
        'height',
        'sort',
        'extra',
    ];

    protected $casts = [
        'size' => 'integer',
        'status' => 'integer',
        'duration' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'sort' => 'integer',
        'extra' => 'array',
    ];

    /**
     * 获取文件URL（完整URL）
     */
    public function getUrlAttribute()
    {
        if (!$this->path) {
            return null;
        }

        // 获取站点URL
        $siteUrl = $this->getSiteUrl();

        // 本地存储
        if ($this->storage === 'local') {
            return rtrim($siteUrl, '/') . '/storage/' . $this->path;
        }

        // 云存储（暂时返回null，后续扩展）
        return null;
    }

    /**
     * 获取站点URL
     */
    protected function getSiteUrl(): string
    {
        // 尝试从缓存或数据库获取站点配置
        try {
            $config = \App\Modules\System\Models\WebsiteConfig::first();
            if ($config && $config->site_url) {
                return $config->site_url;
            }
        } catch (\Exception $e) {
            // 如果获取失败，使用Laravel配置的APP_URL
        }

        // 回退到Laravel配置的APP_URL
        return config('app.url', 'http://localhost');
    }

    /**
     * 获取文件大小文本
     */
    public function getSizeTextAttribute()
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * 获取视频封面URL（暂时返回null，后续扩展）
     */
    public function getVideoCoverUrlAttribute()
    {
        // TODO: 实现视频封面生成逻辑
        return null;
    }

    /**
     * 获取视频时长（暂时返回null，后续扩展）
     */
    public function getDurationAttribute()
    {
        // TODO: 实现视频时长获取逻辑
        return null;
    }

    /**
     * 获取视频ID（暂时返回null，后续扩展）
     */
    public function getVideoIdAttribute()
    {
        // TODO: 实现视频ID生成逻辑
        return null;
    }

    /**
     * 获取缩略图URL（暂时返回null，后续扩展）
     */
    public function getThumbnailUrlAttribute()
    {
        // TODO: 实现缩略图生成逻辑
        return null;
    }

    /**
     * 创建文件记录
     */
    public static function createFile(
        string $name,
        string $path,
        string $storage = 'local',
        int $size = 0,
        string $mimeType = 'application/octet-stream',
        array $options = []
    ): static {
        return static::create(array_merge($options, [
            'name' => $name,
            'path' => $path,
            'storage' => $storage,
            'size' => $size,
            'mime_type' => $mimeType,
        ]));
    }

    /**
     * 创建本地文件记录（向后兼容）
     */
    public static function createLocalFile(
        string $name,
        string $path,
        int $size = 0,
        string $mimeType = 'application/octet-stream',
        array $options = []
    ): static {
        return static::createFile($name, $path, 'local', $size, $mimeType, $options);
    }

    /**
     * 创建云存储文件记录（向后兼容）
     */
    public static function createCloudFile(
        string $name,
        string $path,
        string $storageDriver,
        int $size = 0,
        string $mimeType = 'application/octet-stream',
        array $options = []
    ): static {
        return static::createFile($name, $path, $storageDriver, $size, $mimeType, $options);
    }
}
