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

namespace App\Modules\Comment\Resources;

use Exception;
use Illuminate\Http\Resources\Json\JsonResource;
use Log;

class CommentResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'targetId' => $this->target_id,
            'targetType' => $this->target_type,
            'parentId' => $this->parent_id,
            'content' => $this->content,
            'contentType' => $this->content_type,
            'videoUrl' => $this->video_url,
            'images' => $this->images,
            'likeCount' => $this->like_count,
            'replyCount' => $this->reply_count ?? 0,
            'status' => $this->status,
            'statusText' => $this->status_text, // 使用 Accessor
            'isLiked' => $this->getIsLikedAttribute($request->user()),
            'createdAt' => $this->created_at,
            'createdAtHuman' => $this->created_at?->locale('zh_CN')->diffForHumans(),
            'updatedAt' => $this->updated_at,
            'updatedAtHuman' => $this->updated_at?->locale('zh_CN')->diffForHumans(),

            // IP地址信息
            'ip' => $this->ip,
            'ipCountry' => $this->ip_country,
            'ipRegion' => $this->ip_region,
           // 'ipCity' => $this->ip_city,
           // 'ipIsp' => $this->ip_isp,
           // 'ipLocation' => $this->ip_location,
            //'userAgent' => $this->user_agent,
            // 操作权限：通过Policy判断是否可以删除
            'canDelete' => $request->user() ? $request->user()->can('delete', $this->resource) : false,
            // 是否为作者：判断评论者是否是目标对象（动态/文章等）的作者
            'isAuthor' => $this->isAuthor(),

            'user' => $this->user ? [
                'id' => (string) $this->user->id,
                'username' => $this->user->username,
                'nickname' => $this->user->nickname,
                'avatarUrl' => $this->user->avatar ? config('app.url') . '/storage/' . $this->user->avatar : config('app.url') . '/assets/default_avatars.png',
            ] : null,
            // 移除likes字段，避免关联查询问题。点赞状态通过isLiked字段传递
            'replies' => $this->when($this->replies, function () {
                return $this->replies->map(function ($reply) {
                    return new CommentResource($reply);
                });
            }),

            // @用户列表
            'mentions' => $this->whenLoaded('mentions', function () {
                return $this->mentions->map(function ($mention) {
                    return [
                        'userId' => (string) $mention->user_id,
                        'username' => $mention->username,
                        'nickname' => $mention->nickname_at_time,
                        'avatarUrl' => $mention->user->avatar ? config('app.url') . '/storage/' . $mention->user->avatar : config('app.url') . '/assets/default_avatars.png',
                    ];
                });
            }),

            // #话题列表
            'topics' => $this->whenLoaded('topics', function () {
                return $this->topics->map(function ($topic) {
                    return [
                        'topicId' => (string) $topic->id,
                        'id' => (string) $topic->id,
                        'name' => $topic->name,
                        'description' => $topic->description,
                        'cover' => $topic->cover,
                        'postCount' => $topic->post_count,
                        'followerCount' => $topic->follower_count,
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
     * 获取审核记录（人工审核 + AI审核）
     * 从统一的 ReviewLog 表读取.
     */
    protected function getAuditRecords(): array
    {
        $records = [];

        try {
            // 从统一的 ReviewLog 表查询审核记录
            $logs = \App\Modules\Review\Models\ReviewLog::where('reviewable_type', \App\Modules\Comment\Models\Comment::class)
                ->where('reviewable_id', $this->id)
                ->with('admin:id,username,nickname')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($logs as $log) {
                if ($log->channel === 'manual') {
                    // 人工审核记录 - 使用 Comment 模型的静态方法复用状态文本转换逻辑
                    $statusText = \App\Modules\Comment\Models\Comment::getStatusText((int) $log->new_status);

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
                            'status' => \App\Modules\Comment\Models\Comment::STATUS_PENDING,
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

                        // 将审核结果状态映射为 Comment 的数字状态
                        $commentStatus = match ($status) {
                            'pass', 'approved' => \App\Modules\Comment\Models\Comment::STATUS_PUBLISHED,
                            'reject', 'rejected' => \App\Modules\Comment\Models\Comment::STATUS_REJECTED,
                            default => \App\Modules\Comment\Models\Comment::STATUS_PENDING,
                        };

                        $records[] = [
                            'channel' => 'AI',
                            'channelType' => 'ai',
                            'pluginName' => $log->plugin_name,
                            'status' => $commentStatus,
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
            Log::warning('获取评论审核记录失败', [
                'comment_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $records;
    }

    /**
     * 判断评论者是否是目标对象（动态/文章等）的作者.
     */
    protected function isAuthor(): bool
    {
        // 使用 Service 层传递的 target_user_id（避免重复查询）
        $targetUserId = $this->target_user_id ?? null;

        return $targetUserId && $targetUserId === $this->user_id;
    }
}
