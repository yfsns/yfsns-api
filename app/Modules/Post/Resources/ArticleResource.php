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

use Exception;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Log;

class ArticleResource extends JsonResource
{
    public function toArray($request)
    {
        // 设置用户交互状态
        $this->setUserInteractionStatus($request);

        return [
            'id' => (string) $this->id,
            'type' => 'article',
            'title' => $this->title,
            'contentHtml' => $this->content_html,
            'excerpt' => Str::limit(strip_tags($this->content_html ?? ''), 200), // 文章摘要
            'status' => $this->status,
            'statusText' => $this->status_text, // 使用 Accessor
            'visibility' => $this->visibility,
            'visibilityText' => $this->visibility_text, // 使用 Accessor
            'location' => $this->location_id ? $this->location : null, // 当没有location_id时不返回location字段
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
            'readingTime' => $this->estimateReadingTime(), // 预估阅读时间（分钟）
            'wordCount' => $this->getWordCount(), // 字数统计
            'likeCount' => $this->likes_count ?? $this->like_count ?? $this->likes->count() ?? 0,
            'commentCount' => $this->comments_count ?? $this->comment_count ?? $this->comments->count() ?? 0,
            'collectCount' => $this->collects_count ?? $this->collect_count ?? $this->collects->count() ?? 0,
            'isLiked' => $this->isLiked ?? $this->is_liked ?? false,
            'isCollected' => $this->isCollected ?? $this->is_favorited ?? false,
            // 使用Policy动态检查权限
            'canEdit' => $request->user() ? $request->user()->can('update', $this->resource) : false,
            'canDelete' => $request->user() ? $request->user()->can('delete', $this->resource) : false,
            'images' => $this->whenLoaded('files', function () {
                return $this->files->filter(function ($file) {
                    return $file->type === \App\Modules\File\Models\File::TYPE_IMAGE;
                })->map(function ($file) {
                    return [
                        'fileId' => $file->id,
                        'name' => $file->name,
                        'url' => $file->path,
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
                        'url' => $file->path,
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
                    'url' => $cover->path,
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
                        'url' => $file->path,
                        'size' => $file->size,
                        'mimeType' => $file->mime_type,
                    ];
                });
            }),
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

    /**
     * 设置用户交互状态（点赞、收藏等）
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
     * 获取审核记录（人工审核 + AI审核）
     */
    protected function getAuditRecords(): array
    {
        $records = [];

        try {
            // 从统一的 ReviewLog 表查询审核记录
            $logs = \App\Modules\Review\Models\ReviewLog::where('reviewable_type', \App\Modules\Post\Models\Post::class)
                ->where('reviewable_id', $this->id)
                ->with('admin:id,username,nickname')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($logs as $log) {
                if ($log->channel === 'manual') {
                    // 人工审核记录
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
                    // AI审核记录
                    $result = $log->audit_result ?? [];
                    $status = $result['status'] ?? 'pending';

                    // 检查是否是错误状态
                    $isError = isset($result['error']) && $result['error'] === true;

                    if ($isError) {
                        // 错误状态
                        $records[] = [
                            'channel' => 'AI',
                            'channelType' => 'ai',
                            'pluginName' => $log->plugin_name,
                            'status' => \App\Modules\Post\Models\Post::STATUS_PENDING,
                            'statusText' => '审核失败',
                            'isError' => true,
                            'reason' => $result['message'] ?? $log->remark ?? '审核服务异常',
                            'errorMessage' => $result['message'] ?? '审核服务异常',
                            'createdAt' => $log->created_at?->toIso8601String(),
                            'createdAtHuman' => $log->created_at?->diffForHumans(),
                        ];
                    } else {
                        // 正常审核结果
                        $statusText = [
                            'pass' => '审核通过',
                            'approved' => '审核通过',
                            'reject' => '审核拒绝',
                            'rejected' => '审核拒绝',
                            'pending' => '待审核',
                        ][$status] ?? $status;

                        // 将审核结果状态映射为 Post 的数字状态
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
                            'auditResult' => $result, // 完整的审核结果
                            'previousStatus' => (int) $log->previous_status,
                            'createdAt' => $log->created_at?->toIso8601String(),
                            'createdAtHuman' => $log->created_at?->diffForHumans(),
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            // 如果获取审核记录失败，返回空数组
            Log::warning('获取文章审核记录失败', [
                'post_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $records;
    }
}