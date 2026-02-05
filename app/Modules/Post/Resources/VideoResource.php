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
        // 这里可以调用FFmpeg或其他服务获取视频时长
        // 暂时从数据库字段获取或返回null
        return $file->metadata['duration'] ?? null;
    }

    /**
     * 获取视频分辨率
     */
    protected function getVideoResolution($file): ?string
    {
        // 获取视频分辨率信息
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

        $totalDuration = 0;
        foreach ($this->files->where('type', 'video') as $file) {
            $duration = $this->getVideoDuration($file);
            if ($duration) {
                $totalDuration = max($totalDuration, $duration);
            }
        }

        return $totalDuration > 0 ? $totalDuration : null;
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
     * 是否有多种质量
     */
    protected function hasMultipleQuality(): bool
    {
        if (!$this->relationLoaded('files')) {
            return false;
        }

        return $this->files->where('type', 'video')->count() > 1;
    }

    /**
     * 获取可用质量列表
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
            ->values()
            ->toArray();
    }
}
{
    public function toArray($request)
    {
        // 设置用户交互状态
        $this->setUserInteractionStatus($request);

        return [
            'id' => (string) $this->id,
            'type' => 'video',
            'title' => $this->title,
            'contentHtml' => $this->content_html,
            'status' => $this->status,
            'statusText' => $this->status_text,
            'visibility' => $this->visibility,
            'visibilityText' => $this->visibility_text,
            'location' => $this->location_id ? $this->location : null,
            'isTop' => (bool) ($this->is_top ?? false),
            'isEssence' => (bool) ($this->is_essence ?? false),
            'isRecommend' => (bool) ($this->is_recommend ?? false),
            'author' => $this->whenLoaded('user', function () {
                return [
                    'id' => (string) $this->user->id,
                    'username' => $this->user->username,
                    'nickname' => $this->user->nickname,
                    'avatarUrl' => $this->user->avatar_url,
                ];
            }),
            'createdAtHuman' => $this->created_at ? $this->created_at->locale('zh_CN')->diffForHumans() : '',
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
            'likeCount' => $this->likes_count ?? $this->like_count ?? $this->likes->count() ?? 0,
            'commentCount' => $this->comments_count ?? $this->comment_count ?? $this->comments->count() ?? 0,
            'collectCount' => $this->collects_count ?? $this->collect_count ?? $this->collects->count() ?? 0,
            'isLiked' => $this->isLiked ?? $this->is_liked ?? false,
            'isCollected' => $this->isCollected ?? $this->is_favorited ?? false,
            'canEdit' => $request->user() ? $request->user()->can('update', $this->resource) : false,
            'canDelete' => $request->user() ? $request->user()->can('delete', $this->resource) : false,
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
            // @用户列表
            'mentions' => $this->whenLoaded('mentions', function () {
                return $this->mentions->map(function ($mention) {
                    return [
                        'userId' => (string) $mention->user_id,
                        'username' => $mention->username,
                        'nickname' => $mention->nickname_at_time,
                        'avatarUrl' => $mention->user->avatar_url,
                    ];
                });
            }),
            // #话题列表
            'topics' => $this->whenLoaded('topics', function () {
                return $this->topics->map(function ($topic) {
                    return [
                        'id' => (string) $topic->id,
                        'name' => $topic->name,
                        'cover' => $topic->cover,
                        'position' => $topic->pivot->position ?? 0,
                    ];
                });
            }),
            // 审核记录（管理员接口）
            'auditRecords' => $this->when(
                $request->is('api/admin/*') || $request->is('admin/*'),
                function () {
                    return $this->getAuditRecords();
                }
            ),
        ];
    }

    /**
     * 获取视频时长
     */
    protected function getVideoDuration($file): ?int
    {
        // 这里可以调用FFmpeg或其他服务获取视频时长
        // 暂时从数据库字段获取或返回null
        return $file->metadata['duration'] ?? null;
    }

    /**
     * 获取视频分辨率
     */
    protected function getVideoResolution($file): ?string
    {
        // 获取视频分辨率信息
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

        $totalDuration = 0;
        foreach ($this->files->where('type', 'video') as $file) {
            $duration = $this->getVideoDuration($file);
            if ($duration) {
                $totalDuration = max($totalDuration, $duration);
            }
        }

        return $totalDuration > 0 ? $totalDuration : null;
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
     * 是否有多种质量
     */
    protected function hasMultipleQuality(): bool
    {
        if (!$this->relationLoaded('files')) {
            return false;
        }

        return $this->files->where('type', 'video')->count() > 1;
    }

    /**
     * 获取可用质量列表
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
            ->values()
            ->toArray();
    }

    /**
     * 设置用户交互状态
     */
    protected function setUserInteractionStatus($request): void
    {
        $user = $request->user();

        // 设置点赞状态
        if ($this->resource->relationLoaded('likes')) {
            $this->resource->isLiked = $user ? $this->resource->likes->contains('user_id', $user->id) : false;
        } else {
            $this->resource->isLiked = false;
        }

        // 设置收藏状态
        if ($this->resource->relationLoaded('collects')) {
            $this->resource->isCollected = $user ? $this->resource->collects->contains('user_id', $user->id) : false;
        } else {
            $this->resource->isCollected = false;
        }
    }

    /**
     * 获取审核记录
     */
    protected function getAuditRecords(): array
    {
        $records = [];

        try {
            $logs = \App\Modules\Review\Models\ReviewLog::where('reviewable_type', \App\Modules\Post\Models\Post::class)
                ->where('reviewable_id', $this->id)
                ->with('admin:id,username,nickname')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($logs as $log) {
                if ($log->channel === 'manual') {
                    $statusText = \App\Modules\Post\Models\Post::getStatusText((int) $log->new_status);

                    $records[] = [
                        'channel' => '人工',
                        'channelType' => 'manual',
                        'status' => (int) $log->new_status,
                        'statusText' => $statusText,
                        'reason' => $log->remark,
                        'adminId' => $log->admin_id ? (string) $log->admin_id : null,
                        'adminName' => $log->relationLoaded('admin') && $log->admin
                            ? ($log->admin->nickname ?: $log->admin->username)
                            : null,
                        'previousStatus' => (int) $log->previous_status,
                        'createdAt' => $log->created_at?->toIso8601String(),
                        'createdAtHuman' => $log->created_at?->diffForHumans(),
                    ];
                } else {
                    $result = $log->audit_result ?? [];
                    $status = $result['status'] ?? 'pending';

                    $statusText = [
                        'pass' => '审核通过',
                        'approved' => '审核通过',
                        'reject' => '审核拒绝',
                        'rejected' => '审核拒绝',
                        'pending' => '待审核',
                    ][$status] ?? $status;

                    $postStatus = match ($status) {
                        'pass', 'approved' => \App\Modules\Post\Models\Post::STATUS_PUBLISHED,
                        'reject', 'rejected' => \App\Modules\Post\Models\Post::STATUS_REJECTED,
                        default => \App\Modules\Post\Models\Post::STATUS_PENDING,
                    };

                    $records[] = [
                        'channel' => 'AI',
                        'channelType' => 'ai',
                        'pluginName' => $log->plugin_name,
                        'status' => $postStatus,
                        'statusText' => $statusText,
                        'reason' => $result['reason'] ?? $result['message'] ?? $log->remark,
                        'score' => $result['score'] ?? null,
                        'details' => $result['details'] ?? null,
                        'auditResult' => $result,
                        'previousStatus' => (int) $log->previous_status,
                        'createdAt' => $log->created_at?->toIso8601String(),
                        'createdAtHuman' => $log->created_at?->diffForHumans(),
                    ];
                }
            }
        } catch (Exception $e) {
            Log::warning('获取视频审核记录失败', [
                'post_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $records;
    }
}