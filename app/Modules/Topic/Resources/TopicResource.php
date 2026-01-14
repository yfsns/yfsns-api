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

namespace App\Modules\Topic\Resources;

use App\Modules\Review\Models\ReviewLog;
use App\Modules\Topic\Models\Topic;
use Exception;
use Illuminate\Http\Resources\Json\JsonResource;
use Log;

class TopicResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'cover' => $this->cover,
            // 支持withCount的结果（posts_count, followers_count）
            'postCount' => $this->posts_count ?? $this->post_count ?? 0,
            'followerCount' => $this->followers_count ?? $this->follower_count ?? 0,
            'viewCount' => $this->view_count ?? 0,
            'isHot' => $this->isHot(),
            'isHotText' => $this->isHot() ? '热门' : '普通',
            'hotScore' => $this->getHotScore(),
            'hotScoreText' => $this->formatHotScoreText(),
            'status' => (int) $this->status,
            'statusText' => match ((int) $this->status) {
                Topic::STATUS_PENDING => '待审核',
                Topic::STATUS_PUBLISHED, Topic::STATUS_ACTIVE => '已发布',
                Topic::STATUS_REJECTED, Topic::STATUS_DISABLED => '已拒绝',
                default => '未知',
            },

            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,

            // 审核记录（管理员接口）
            'auditRecords' => $this->getAuditRecords(),
        ];
    }

    protected function isHot(): bool
    {
        $posts = $this->posts_count ?? $this->post_count ?? 0;
        $followers = $this->followers_count ?? $this->follower_count ?? 0;

        return ($posts + $followers) >= 50 || $followers >= 20;
    }

    protected function getHotScore(): int
    {
        $posts = $this->posts_count ?? $this->post_count ?? 0;
        $followers = $this->followers_count ?? $this->follower_count ?? 0;

        return (int) ($followers * 2 + $posts);
    }

    protected function formatHotScoreText(): string
    {
        $score = $this->getHotScore();

        return match (true) {
            $score >= 200 => '爆款',
            $score >= 100 => '高热度',
            $score >= 50 => '热门',
            $score >= 20 => '较热',
            default => '普通',
        };
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
            $logs = ReviewLog::where('reviewable_type', Topic::class)
                ->where('reviewable_id', $this->id)
                ->with('admin:id,username,nickname')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($logs as $log) {
                if ($log->channel === ReviewLog::CHANNEL_MANUAL) {
                    // 人工审核记录
                    $statusText = match ((int) $log->new_status) {
                        Topic::STATUS_PENDING => '待审核',
                        Topic::STATUS_PUBLISHED => '已发布',
                        Topic::STATUS_REJECTED => '已拒绝',
                        default => (string) $log->new_status,
                    };

                    $records[] = [
                        'channel' => '人工',
                        'channelType' => ReviewLog::CHANNEL_MANUAL,
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
                            'channelType' => ReviewLog::CHANNEL_AI,
                            'pluginName' => $log->plugin_name,
                            'status' => Topic::STATUS_PENDING, // 审核失败，保持待审核状态
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

                        // 将审核结果状态映射为 Topic 的数字状态
                        $topicStatus = match ($status) {
                            'pass', 'approved' => Topic::STATUS_PUBLISHED,
                            'reject', 'rejected' => Topic::STATUS_REJECTED,
                            default => Topic::STATUS_PENDING,
                        };

                        $records[] = [
                            'channel' => 'AI',
                            'channelType' => ReviewLog::CHANNEL_AI,
                            'pluginName' => $log->plugin_name,
                            'status' => $topicStatus,
                            'statusText' => $statusText,
                            'reason' => $result['reason'] ?? $result['message'] ?? $log->remark,
                            'score' => $result['score'] ?? null,
                            'details' => $result['details'] ?? null,
                            'auditResult' => $result, // 完整的 AI 审核结果
                            'previousStatus' => (int) $log->previous_status,
                            'createdAt' => $log->created_at?->toIso8601String(),
                            'createdAtHuman' => $log->created_at?->diffForHumans(),
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('获取话题审核记录失败', [
                'topic_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $records;
    }
}
