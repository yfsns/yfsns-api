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

namespace App\Modules\Post\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AdminPostResource extends JsonResource
{
    public function toArray($request)
    {
        // 获取已加载的关联数据
        $author = $this->whenLoaded('user');
        $files = $this->whenLoaded('files') ?: collect([]);
        $topics = $this->whenLoaded('topics') ?: collect([]);

        // 处理文件数据（如果有关联）
        $fileData = [];
        if ($files instanceof \Illuminate\Database\Eloquent\Collection && $files->isNotEmpty()) {
            $fileData = $files->map(function ($file) {
                return [
                    'id' => (string) $file->id,
                    'name' => $file->name,
                    'path' => $file->path,
                    'type' => $file->type,
                    'size' => $file->size,
                    'mimeType' => $file->mime_type,
                    'storage' => $file->storage,
                    'thumbnail' => $file->thumbnail,
                    'createdAt' => $file->created_at?->format('Y-m-d H:i:s'),
                ];
            })->toArray();
        }

        return [
            'id' => (string) $this->id,
            'type' => $this->type ?? 'post',
            'title' => $this->title,
            'contentHtml' => $this->content_html,
            'contentHtmlPreview' => Str::limit(strip_tags($this->content_html ?? ''), 300),
            'status' => $this->status,
            'statusText' => $this->status_text,
            'visibility' => $this->visibility,
            'visibilityText' => $this->visibility_text,
            'location' => $this->location,

            // 作者信息
            'author' => $author ? [
                'id' => (string) $author->id,
                'username' => $author->username,
                'nickname' => $author->nickname,
                'avatarUrl' => $author->avatar ? config('app.url') . '/storage/' . $author->avatar : config('app.url') . '/assets/default_avatars.png',
            ] : null,

            // 文件信息
            'files' => $fileData,

            // 话题信息 - 返回话题名称数组
            'topics' => ($topics instanceof \Illuminate\Database\Eloquent\Collection)
                ? $topics->pluck('name')->toArray()
                : [],

            // 统计信息
            'likeCount' => $this->like_count ?? 0,
            'commentCount' => $this->comment_count ?? 0,
            'collectCount' => $this->collect_count ?? 0,
            'viewCount' => $this->view_count ?? 0,
            'repostCount' => $this->repost_count ?? 0,

            // 时间信息
            'publishedAt' => $this->published_at?->format('Y-m-d H:i:s'),
            'auditedAt' => $this->audited_at?->format('Y-m-d H:i:s'),
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),

            // 管理员专用字段
            'auditRecords' => $this->getAuditRecords(),
            'rawContent' => $this->content,
            'internalNotes' => $this->internal_notes ?? null,
            'ip' => $this->ip,
            'ipLocation' => $this->ip_location,
            'device' => $this->device,

            // 管理权限 - 管理员始终可以操作
            'canEdit' => true,
            'canDelete' => true,
            'canReview' => true,
        ];
    }

    /**
     * 获取审核记录（人工审核 + AI审核）
     * 从统一的 ReviewLog 表读取.
     */
    protected function getAuditRecords(): array
    {
        $records = [];

        // 从统一的 ReviewLog 表查询审核记录
        $logs = \App\Modules\Review\Models\ReviewLog::where('reviewable_type', \App\Modules\Post\Models\Post::class)
            ->where('reviewable_id', $this->id)
            ->with('admin:id,username,nickname')
            ->orderBy('created_at', 'desc')
            ->get();

        // 审核渠道映射配置
        $channelMappings = [
            'manual' => [
                'displayName' => '人工',
                'channelType' => 'manual',
                'handler' => 'processManualRecord',
            ],
            'ai' => [
                'displayName' => 'AI',
                'channelType' => 'ai',
                'handler' => 'processAiRecord',
            ],
        ];

        foreach ($logs as $log) {
            $channelConfig = $channelMappings[$log->channel] ?? null;
            if (!$channelConfig) {
                continue; // 跳过未知渠道
            }

            $method = $channelConfig['handler'];
            if (method_exists($this, $method)) {
                $record = $this->$method($log, $channelConfig);
                if ($record) {
                    $records[] = $record;
                }
            }
        }

        // 按创建时间倒序排列
        usort($records, function ($a, $b) {
            $timeA = $a['createdAt'] ? strtotime($a['createdAt']) : 0;
            $timeB = $b['createdAt'] ? strtotime($b['createdAt']) : 0;

            return $timeB <=> $timeA;
        });

        return $records;
    }

    /**
     * 处理人工审核记录
     */
    protected function processManualRecord($log, $channelConfig): array
    {
        $statusText = match ((int) $log->new_status) {
            \App\Modules\Post\Models\Post::STATUS_PENDING => '待审核',
            \App\Modules\Post\Models\Post::STATUS_PUBLISHED => '已发布',
            \App\Modules\Post\Models\Post::STATUS_REJECTED => '已拒绝',
            default => (string) $log->new_status,
        };

        return [
            'channel' => $channelConfig['displayName'],
            'channelType' => $channelConfig['channelType'],
            'status' => (int) $log->new_status,
            'statusText' => $statusText,
            'reason' => $log->remark,
            'adminId' => $log->admin_id ? (string) $log->admin_id : null,
            'adminName' => $log->relationLoaded('admin') && $log->admin
                ? ($log->admin->nickname ?: $log->admin->username)
                : null,
            'previousStatus' => (int) $log->previous_status,
            'createdAt' => $log->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 处理AI审核记录
     */
    protected function processAiRecord($log, $channelConfig): array
    {
        $result = $log->audit_result ?? [];
        $status = $result['status'] ?? 'pending';

        // 检查是否是错误状态
        $isError = isset($result['error']) && $result['error'] === true;

        if ($isError) {
            return [
                'channel' => $channelConfig['displayName'],
                'channelType' => $channelConfig['channelType'],
                'pluginName' => $log->plugin_name,
                'status' => \App\Modules\Post\Models\Post::STATUS_PENDING,
                'statusText' => '审核失败',
                'isError' => true,
                'reason' => $result['message'] ?? $log->remark ?? '审核服务异常',
                'errorMessage' => $result['message'] ?? '审核服务异常',
                'createdAt' => $log->created_at?->format('Y-m-d H:i:s'),
            ];
        }

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

        return [
            'channel' => $channelConfig['displayName'],
            'channelType' => $channelConfig['channelType'],
            'pluginName' => $log->plugin_name,
            'status' => $postStatus,
            'statusText' => $statusText,
            'reason' => $result['reason'] ?? $result['message'] ?? $log->remark,
            'score' => $result['score'] ?? null,
            'details' => $result['details'] ?? null,
            'createdAt' => $log->created_at?->format('Y-m-d H:i:s'),
        ];
    }

}
