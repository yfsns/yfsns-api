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

namespace App\Modules\Review\Services;

// 暂时移除插件接口实现，待重新设计插件系统后启用
use App\Modules\Review\Models\ReviewLog;

use function array_slice;

use Illuminate\Database\Eloquent\Model;

use function get_class;
use function gettype;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function in_array;

/**
 * 审核服务
 *
 * 【核心功能】
 * 1. 提供人工审核功能和审核日志记录
 * 2. 支持审核不同类型的内容（article、post、thread、comment）
 *
 * 【插件标准接口实现】
 * 此服务实现了 PendingContentProviderInterface 接口，供插件开发者获取待审核内容。
 */

/**
 * 审核服务 - 核心业务逻辑
 *
 * 提供统一的审核功能，支持多态关联的各种内容类型
 */
class ReviewService
{
    /**
     * 内容类型配置
     */
    protected array $contentTypes = [
        'article' => [
            'model' => \App\Modules\Article\Models\Article::class,
            'pending_status' => 'pending',
        ],
        'post' => [
            'model' => \App\Modules\Post\Models\Post::class,
            'pending_status' => 0,
        ],
        'thread' => [
            'model' => \App\Modules\Forum\Models\ForumThread::class,
            'pending_status' => 'pending',
        ],
        'comment' => [
            'model' => \App\Modules\Forum\Models\ForumReply::class,
            'pending_status' => 0,
        ],
        'topic' => [
            'model' => \App\Modules\Post\Models\Topic::class,
            'pending_status' => 0,
        ],
    ];
    /**
     * 人工审核内容
     *
     * @param Model $reviewable 被审核的内容（Article、Post、Thread等）
     * @param string $action 审核动作：'approve' 或 'reject'
     * @param string|null $remark 审核备注/原因
     * @param int|null $adminId 管理员ID
     * @param array|null $extraData 扩展数据（各模块可自定义参数）
     */
    public function manualReview(
        Model $reviewable,
        string $action,
        ?string $remark = null,
        ?int $adminId = null,
        ?array $extraData = null
    ): ReviewLog {
        $currentStatus = $reviewable->status;

        // 根据模型类型和审核动作确定新状态
        $newStatus = $this->getNewStatusForAction($reviewable, $action);

        // 更新状态和时间戳
        $reviewable->status = $newStatus;
        $reviewable->audited_at = now();

        // 如果是发布，设置发布时间
        if ($this->isPublishedStatus($reviewable, $newStatus)) {
            if (empty($reviewable->published_at)) {
                $reviewable->published_at = now();
            }
        }

        $reviewable->save();

        // 评论审核逻辑：后端只处理真实状态更新
        if ($this->isCommentApproved($reviewable, $currentStatus, $newStatus)) {
            // 审核通过：增加评论计数
            $this->incrementPostCommentCount($reviewable);
        }

        // 动态审核逻辑：处理转发计数
        if ($this->isPostApproved($reviewable, $currentStatus, $newStatus)) {
            // 审核通过：如果有转发，增加原动态的转发计数
            $this->incrementRepostCount($reviewable);
        }

        // 审核拒绝：不做任何操作（前端处理乐观更新的撤销）

        // 记录审核日志
        return ReviewLog::create([
            'reviewable_type' => get_class($reviewable),
            'reviewable_id' => $reviewable->id,
            'channel' => ReviewLog::CHANNEL_MANUAL,
            'admin_id' => $adminId ?? auth()->id(),
            'previous_status' => (string) $currentStatus,
            'new_status' => (string) $newStatus,
            'remark' => $remark,
            'extra_data' => $extraData,
        ]);
    }

    /**
     * 批量审核内容
     */
    public function batchManualReview(array $reviews, ?int $adminId = null): array
    {
        $results = [];

        DB::transaction(function () use ($reviews, $adminId, &$results) {
            foreach ($reviews as $review) {
                $model = $review['model'];
                $action = $review['action'];
                $remark = $review['remark'] ?? null;
                $extraData = $review['extra_data'] ?? null;

                $log = $this->manualReview($model, $action, $remark, $adminId, $extraData);
                $results[] = [
                    'success' => true,
                    'content_id' => $model->id,
                    'content_type' => get_class($model),
                    'log' => $log,
                ];
            }
        });

        return $results;
    }


    /**
     * 获取审核统计信息
     */
    public function getReviewStats(?string $contentType = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = ReviewLog::query();

        if ($contentType) {
            $query->byContentType($contentType);
        }

        if ($dateFrom && $dateTo) {
            $query->dateRange($dateFrom, $dateTo);
        }

        $total = $query->count();
        $approved = (clone $query)->approved()->count();
        $rejected = (clone $query)->rejected()->count();
        $manual = (clone $query)->manual()->count();
        $ai = (clone $query)->ai()->count();

        return [
            'total_reviews' => $total,
            'approved_count' => $approved,
            'rejected_count' => $rejected,
            'manual_reviews' => $manual,
            'ai_reviews' => $ai,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
        ];
    }

    /**
     * 根据模型类型和审核动作确定新状态
     */
    protected function getNewStatusForAction(Model $model, string $action): mixed
    {
        $class = get_class($model);

        // Post, Comment, Topic用数字状态
        if (str_contains($class, 'Post') || str_contains($class, 'Comment') || str_contains($class, 'Topic')) {
            return match ($action) {
                'approve' => 1, // published
                'reject' => 2,  // rejected
                'pending' => 0, // pending
                default => 0,   // 默认待审核
            };
        }

        // Article, ForumThread用字符串状态
        return match ($action) {
            'approve' => 'published',
            'reject' => 'rejected',
            'pending' => 'pending',
            default => 'pending',
        };
    }

    /**
     * 判断是否为发布状态
     */
    protected function isPublishedStatus(Model $model, $status): bool
    {
        $class = get_class($model);

        // Post, Comment, Topic用数字状态
        if (str_contains($class, 'Post') || str_contains($class, 'Comment') || str_contains($class, 'Topic')) {
            return $status == 1;
        }

        // Article, ForumThread用字符串状态
        return $status === 'published';
    }



    /**
     * 获取审核记录.
     *
     * @param Model       $reviewable 被审核的内容
     * @param null|string $channel    筛选渠道（可选）
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLogs(Model $reviewable, ?string $channel = null)
    {
        $query = ReviewLog::where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->with('admin:id,username,nickname')
            ->orderBy('created_at', 'desc');

        if ($channel) {
            $query->where('channel', $channel);
        }

        return $query->get();
    }

    /**
     * 获取最新的审核记录.
     *
     * @param Model       $reviewable 被审核的内容
     * @param null|string $channel    筛选渠道（可选）
     */
    public function getLatestLog(Model $reviewable, ?string $channel = null): ?ReviewLog
    {
        $query = ReviewLog::where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->with('admin:id,username,nickname')
            ->orderBy('created_at', 'desc');

        if ($channel) {
            $query->where('channel', $channel);
        }

        return $query->first();
    }

    // ==================== PendingContentProviderInterface 实现 ====================

    /**
     * 获取待审核内容列表
     */
    public function getPendingContents(?string $contentType = null, int $limit = 100, int $offset = 0): array
    {
        $types = $contentType ? [$contentType] : array_keys($this->contentTypes);
        $allResults = [];

        foreach ($types as $type) {
            $config = $this->contentTypes[$type] ?? null;
            if (!$config) continue;

            $contents = $config['model']::where('status', $config['pending_status'])
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($contents as $content) {
                $allResults[] = $this->formatContentData($type, $content);
            }
        }

        // 按创建时间排序并分页
        usort($allResults, fn($a, $b) => strtotime($a['created_at']) <=> strtotime($b['created_at']));
        return array_slice($allResults, $offset, $limit);
    }

    /**
     * 获取待审核内容数量
     */
    public function getPendingCount(?string $contentType = null): array
    {
        $types = $contentType ? [$contentType] : array_keys($this->contentTypes);
        $stats = [];
        $total = 0;

        foreach ($types as $type) {
            $config = $this->contentTypes[$type] ?? null;
            if (!$config) continue;

            $count = $config['model']::where('status', $config['pending_status'])->count();
            $stats[$type] = $count;
            $total += $count;
        }

        if (!$contentType) {
            $stats['total'] = $total;
        }

        return $stats;
    }

    /**
     * 根据内容类型和ID获取单个待审核内容
     */
    public function getPendingContent(string $contentType, int $contentId): ?array
    {
        $config = $this->contentTypes[$contentType] ?? null;
        if (!$config) {
            Log::warning('getPendingContent: 未找到内容类型配置', compact('contentType', 'contentId'));
            return null;
        }

        $content = $config['model']::find($contentId);
        if (!$content) {
            Log::info('getPendingContent: 内容不存在', compact('contentType', 'contentId'));
            return null;
        }

        if ($content->status != $config['pending_status']) {
            Log::info('getPendingContent: 内容状态不是待审核', [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'current_status' => $content->status,
                'expected_status' => $config['pending_status'],
            ]);
            return null;
        }

        return $this->formatContentData($contentType, $content);
    }


    /**
     * 格式化内容数据
     */
    protected function formatContentData(string $contentType, Model $content): array
    {
        return [
            'id' => $content->id,
            'content_type' => $contentType,
            'title' => $content->title ?? $content->content ?? '无标题',
            'content' => $content->content ?? $content->body ?? '',
            'status' => $content->status,
            'author' => $content->user?->username ?? '未知',
            'created_at' => $content->created_at,
            'updated_at' => $content->updated_at,
        ];
    }

    // ==================== ContentStatusUpdaterInterface 实现 ====================

    /**
     * 更新内容状态并记录审核日志.
     *
     * 供插件调用，统一处理内容状态更新和审核日志记录。
     */
    public function updateStatusAndLog(
        string $contentType,
        int $contentId,
        $status,
        string $channel = 'ai',
        ?string $pluginName = null,
        ?array $auditResult = null,
        ?string $remark = null
    ): bool {
        // 获取内容对象
        $reviewable = $this->getReviewable($contentType, $contentId);
        if (!$reviewable) {
            Log::error('ContentStatusUpdater: 内容不存在', [
                'content_type' => $contentType,
                'content_id' => $contentId,
            ]);
            return false;
        }

        // 将插件状态转换为内部action
        $action = $this->convertPluginStatusToAction($status, $contentType);

        // 准备审核备注
        $finalRemark = $remark;
        if ($auditResult) {
            $finalRemark = $remark ?: '';
            $finalRemark .= $finalRemark ? ' | ' : '';
            $finalRemark .= 'AI审核结果: ' . json_encode($auditResult);
        }

        // 执行审核
        $log = $this->manualReview(
            $reviewable,
            $action,
            $finalRemark,
            null, // AI审核没有管理员ID
            $auditResult
        );

        Log::info('ContentStatusUpdater: 状态更新成功', [
            'content_type' => $contentType,
            'content_id' => $contentId,
            'old_status' => $reviewable->getOriginal('status'),
            'new_status' => $reviewable->status,
            'channel' => $channel,
            'plugin' => $pluginName,
        ]);

        return true;
    }

    /**
     * 记录审核日志（仅用于特殊场景）.
     */
    public function logAudit(
        string $contentType,
        int $contentId,
        string $channel = 'ai',
        ?string $pluginName = null,
        ?array $auditResult = null,
        ?string $remark = null
    ): bool {
        // 获取内容对象
        $reviewable = $this->getReviewable($contentType, $contentId);
        if (!$reviewable) {
            return false;
        }

        // 创建日志记录
        ReviewLog::create([
            'reviewable_type' => get_class($reviewable),
            'reviewable_id' => $reviewable->id,
            'channel' => $channel,
            'previous_status' => (string)$reviewable->status,
            'new_status' => (string)$reviewable->status, // 状态未变
            'admin_id' => null,
            'remark' => $remark ?: 'AI审核日志',
            'extra_data' => $auditResult,
        ]);

        return true;
    }

    /**
     * 记录审核日志（详细版本）.
     */
    public function logReview(
        string $contentType,
        int $contentId,
        string $channel,
        $previousStatus,
        $newStatus,
        ?string $pluginName = null,
        ?array $auditResult = null,
        ?string $remark = null
    ) {
        // 获取内容对象
        $reviewable = $this->getReviewable($contentType, $contentId);
        if (!$reviewable) {
            Log::error('ContentStatusUpdater: 记录审核日志失败，内容不存在', [
                'content_type' => $contentType,
                'content_id' => $contentId,
            ]);
            return null;
        }

        // 创建审核日志
        $log = ReviewLog::create([
            'reviewable_type' => get_class($reviewable),
            'reviewable_id' => $reviewable->id,
            'channel' => $channel,
            'previous_status' => (string)$previousStatus,
            'new_status' => (string)$newStatus,
            'admin_id' => null, // AI审核没有管理员
            'remark' => $remark ?: match($channel) {
                ReviewLog::CHANNEL_MANUAL => '人工审核',
                ReviewLog::CHANNEL_AI => ($pluginName ? "{$pluginName}审核" : 'AI审核'),
                default => '审核日志',
            },
            'extra_data' => $auditResult,
        ]);

        Log::info('ContentStatusUpdater: 审核日志记录成功', [
            'content_type' => $contentType,
            'content_id' => $contentId,
            'log_id' => $log->id,
            'channel' => $channel,
            'plugin' => $pluginName,
        ]);

        return $log;
    }

    /**
     * 将插件状态转换为内部action.
     */
    private function convertPluginStatusToAction($status, string $contentType): string
    {
        // 对于数字状态的模型 (Post, Comment, Topic)
        if (in_array($contentType, ['post', 'comment', 'topic'])) {
            return match ($status) {
                1, '1', 'published', 'pass', 'approved' => 'approve',
                2, '2', 'rejected', 'reject' => 'reject',
                0, '0', 'pending' => 'pending',
                default => 'pending',
            };
        }

        // 对于字符串状态的模型 (Article, ForumThread)
        return match ($status) {
            'published', 'pass', 'approved' => 'approve',
            'rejected', 'reject' => 'reject',
            'pending' => 'pending',
            default => 'pending',
        };
    }

    /**
     * 检查评论是否被审核通过.
     */
    protected function isCommentApproved(Model $reviewable, $oldStatus, $newStatus): bool
    {
        if (!($reviewable instanceof \App\Modules\Comment\Models\Comment)) {
            return false;
        }

        // 只有当旧状态不是已发布，且新状态是已发布时才算审核通过
        return $oldStatus != \App\Modules\Comment\Models\Comment::STATUS_PUBLISHED &&
               $newStatus == \App\Modules\Comment\Models\Comment::STATUS_PUBLISHED;
    }

    /**
     * 检查评论是否被审核拒绝.
     */
    protected function isCommentRejected(Model $reviewable, $oldStatus, $newStatus): bool
    {
        if (!($reviewable instanceof \App\Modules\Comment\Models\Comment)) {
            return false;
        }

        // 只有当旧状态不是已拒绝，且新状态是已拒绝时才算审核拒绝
        return $oldStatus != \App\Modules\Comment\Models\Comment::STATUS_REJECTED &&
               $newStatus == \App\Modules\Comment\Models\Comment::STATUS_REJECTED;
    }

    /**
     * 检查动态是否被审核通过.
     */
    protected function isPostApproved(Model $post, $currentStatus, $newStatus): bool
    {
        // 检查是否为Post模型，且状态从非发布变为发布
        return $post instanceof \App\Modules\Post\Models\Post &&
               $this->isPublishedStatus($post, $newStatus) &&
               !$this->isPublishedStatus($post, $currentStatus);
    }

    /**
     * 增加动态的转发计数.
     */
    protected function incrementRepostCount(Model $post): void
    {
        // 如果是转发动态，增加原动态的转发计数
        if ($post->repost_id) {
            $originalPost = \App\Modules\Post\Models\Post::find($post->repost_id);
            if ($originalPost) {
                $originalPost->incrementRepostCount();
            }
        }
    }

    /**
     * 增加帖子的评论计数.
     */
    protected function incrementPostCommentCount(Model $comment): void
    {
        if ($comment instanceof \App\Modules\Comment\Models\Comment && $comment->target_type === 'post') {
            \App\Modules\Post\Models\Post::where('id', $comment->target_id)->increment('comment_count');
            Log::info('帖子评论数已增加', ['post_id' => $comment->target_id, 'comment_id' => $comment->id]);
        }
    }

    /**
     * 减少帖子的评论计数.
     */
    protected function decrementPostCommentCount(Model $comment): void
    {
        if ($comment instanceof \App\Modules\Comment\Models\Comment && $comment->target_type === 'post') {
            \App\Modules\Post\Models\Post::where('id', $comment->target_id)->decrement('comment_count');
            Log::info('帖子评论数已减少', ['post_id' => $comment->target_id, 'comment_id' => $comment->id]);
        }
    }

    /**
     * 获取可审核的内容对象.
     */
    private function getReviewable(string $contentType, int $contentId)
    {
        return match ($contentType) {
            'article' => \App\Modules\Article\Models\Article::find($contentId),
            'post' => \App\Modules\Post\Models\Post::find($contentId),
            'thread' => \App\Modules\Forum\Models\ForumThread::find($contentId),
            'comment' => \App\Modules\Comment\Models\Comment::find($contentId),
            'topic' => \App\Modules\Topic\Models\Topic::find($contentId),
            default => null,
        };
    }

}
