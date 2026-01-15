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

namespace App\Modules\Comment\Services;

use App\Modules\Comment\Models\Comment;
use App\Modules\User\Services\UserService;
use App\Modules\Review\Services\ReviewService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminCommentService
{
    protected ReviewService $reviewService;
    protected UserService $userService;

    public function __construct(ReviewService $reviewService, UserService $userService)
    {
        $this->reviewService = $reviewService;
        $this->userService = $userService;
    }

    /**
     * 获取评论列表.
     */
    public function getList(array $params): LengthAwarePaginator
    {
        $query = Comment::query()
            ->with(['user'])
            ->when(isset($params['status']), function ($query) use ($params): void {
                $query->where('status', $params['status']);
            })
            ->when(isset($params['keyword']), function ($query) use ($params): void {
                $query->where('content', 'like', "%{$params['keyword']}%");
            });

        // 分页参数处理
        $perPage = (int) ($params['per_page'] ?? 15);
        $page = $params['page'] ?? null;

        $query = $query->orderBy('created_at', 'desc');

        // 管理后台使用传统分页，显示总数和页码导航
        if ($page !== null) {
            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        return $query->paginate($perPage);
    }

    /**
     * 获取评论详情.
     */
    public function getDetail(int $id): array
    {
        $comment = Comment::with(['user', 'files'])
            ->findOrFail($id);

        // 获取被评论的目标内容
        $target = null;
        if ($comment->target_type === 'post') {
            $target = \App\Modules\Post\Models\Post::find($comment->target_id);
        } elseif ($comment->target_type === 'comment') {
            $target = Comment::find($comment->target_id);
        }

        // 获取审核日志（特别是 AI 审核记录）
        $reviewLogs = $this->reviewService->getLogs($comment);
        $aiReviewLog = $reviewLogs->where('channel', 'ai')->where('plugin_name', 'ContentAudit')->first();

        return [
            'id' => $comment->id,
            'content' => $comment->content,
            'user_id' => $comment->user_id,
            'user' => $comment->user,
            'status' => $comment->status,
            'created_at' => $comment->created_at,
            'target' => $target,
            'files' => $comment->files,
            // 审核日志信息
            'reviewLogs' => $reviewLogs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'channel' => $log->channel,
                    'pluginName' => $log->plugin_name,
                    'previousStatus' => $log->previous_status,
                    'newStatus' => $log->new_status,
                    'remark' => $log->remark,
                    'auditResult' => $log->audit_result, // AI 审核结果，包含 status、reason、score、details 等
                    'adminId' => $log->admin_id,
                    'admin' => $log->admin ? [
                        'id' => $log->admin->id,
                        'username' => $log->admin->username,
                        'nickname' => $log->admin->nickname,
                    ] : null,
                    'createdAt' => $log->created_at,
                ];
            })->values(),
            // AI 审核信息（快速访问）
            'aiReview' => $aiReviewLog ? [
                'status' => $aiReviewLog->audit_result['status'] ?? null, // 'pass' 或 'reject'
                'reason' => $aiReviewLog->audit_result['reason'] ?? null, // 审核原因
                'score' => $aiReviewLog->audit_result['score'] ?? null, // 审核分数
                'details' => $aiReviewLog->audit_result['details'] ?? null, // 详细信息
                'auditResult' => $aiReviewLog->audit_result, // 完整的审核结果
                'createdAt' => $aiReviewLog->created_at,
            ] : null,
        ];
    }

    /**
     * 更新评论状态
     */
    public function updateStatus(int $id, int $status): Comment
    {
        $comment = Comment::findOrFail($id);
        $oldStatus = $comment->status;
        $comment->update(['status' => $status]);

        // 核心逻辑：只有审核通过的评论才计入总数
        // 1. 审核通过：待审核 → 已发布，评论数 +1
        if ($oldStatus === Comment::STATUS_PENDING && $status === Comment::STATUS_PUBLISHED) {
            $this->incrementTargetCommentCount($comment);
            if ($comment->parent_id) {
                Comment::query()
                    ->where('id', $comment->parent_id)
                    ->first()?->incrementReplyCount();
            }
            $comment->updateHotScore();
        }
        // 2. 审核拒绝：已发布 → 拒绝，评论数 -1
        elseif ($oldStatus === Comment::STATUS_PUBLISHED && $status === Comment::STATUS_REJECTED) {
            $this->decrementTargetCommentCount($comment);
            if ($comment->parent_id) {
                Comment::query()
                    ->where('id', $comment->parent_id)
                    ->first()?->decrementReplyCount();
            }
        }

        $this->clearCommentCache($comment);

        // 刷新模型以确保返回最新数据
        return $comment->fresh();
    }

    /**
     * 审核评论（使用统一审核服务）.
     */
    public function audit(int $id, int $status, string $reason = ''): Comment
    {
        $comment = Comment::findOrFail($id);
        $oldStatus = $comment->status;

        // 状态映射：1=通过(PUBLISHED), 2=拒绝(REJECTED)
        $statusMap = [
            1 => Comment::STATUS_PUBLISHED,
            2 => Comment::STATUS_REJECTED,
        ];

        $newStatus = $statusMap[$status] ?? Comment::STATUS_PENDING;

        // 使用统一审核服务进行人工审核
        $this->reviewService->manualReview(
            $comment,
            $newStatus,
            $reason ?: null,
            $this->userService->getCurrentUserId()
        );

        // 如果从待审核变为已发布，更新相关统计
        if ($oldStatus === Comment::STATUS_PENDING && $newStatus === Comment::STATUS_PUBLISHED) {
            // 更新目标（Post/Article）的评论数（待审核时未计入，现在审核通过需要计入）
            $this->incrementTargetCommentCount($comment);

            // 如果是回复，更新父评论的回复数和热门分数
            if ($comment->parent_id) {
                $parentComment = Comment::find($comment->parent_id);
                if ($parentComment) {
                    $parentComment->incrementReplyCount();
                }
            }
            // 更新评论本身的热门分数
            $comment->refresh();
            $comment->updateHotScore();
        }

        $this->clearCommentCache($comment);

        return $comment->fresh();
    }

    /**
     * 删除评论（根据ID）.
     */
    public function deleteById(int $id): void
    {
        $comment = Comment::findOrFail($id);
        $this->delete($comment);
    }

    /**
     * 删除评论（软删除）.
     */
    public function delete(Comment $comment): void
    {
        DB::transaction(function () use ($comment): void {
            // 使用软删除（设置 deleted_at 字段）
            $comment->delete();

            // 更新统计（仅当评论已发布时才减少计数，待审核的评论本来就没计入）
            if ($comment->isPublished()) {
                $this->decrementTargetCommentCount($comment);
            }

            $this->clearCommentCache($comment);
        });
    }

    /**
     * 批量删除评论.
     */
    public function batchDelete(array $ids): void
    {
        $comments = Comment::whereIn('id', $ids)->get();
        foreach ($comments as $comment) {
            $this->delete($comment);
        }
    }

    /**
     * 批量审核评论.
     */
    public function batchAudit(array $ids, int $status, string $reason = ''): array
    {
        // 状态映射：1=通过(PUBLISHED), 2=拒绝(REJECTED)
        $statusMap = [
            1 => Comment::STATUS_PUBLISHED,
            2 => Comment::STATUS_REJECTED,
        ];

        $newStatus = $statusMap[$status] ?? Comment::STATUS_PENDING;

        DB::transaction(function () use ($ids, $newStatus, $reason): void {
            foreach ($ids as $id) {
                $comment = Comment::findOrFail($id);

                // 使用统一审核服务进行人工审核
                $this->reviewService->manualReview(
                    $comment,
                    $newStatus,
                    $reason ?: null,
                    $this->userService->getCurrentUserId()
                );

                $this->clearCommentCache($comment);
            }
        });

        return [
            'success_count' => count($ids),
            'failed_count' => 0,
        ];
    }

    /**
     * 获取评论统计
     */
    public function getStatistics(): array
    {
        // 使用 withTrashed() 包含软删除的记录来统计总数
        $total = Comment::withTrashed()->count();
        $pending = Comment::pending()->count(); // 使用 scope
        $published = Comment::published()->count(); // 使用 scope（兼容 STATUS_NORMAL）
        $rejected = Comment::rejected()->count(); // 使用 scope
        // 已删除的评论通过 deleted_at 字段统计（软删除）
        $deleted = Comment::onlyTrashed()->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'published' => $published,
            'rejected' => $rejected,
            'deleted' => $deleted,
        ];
    }

    /**
     * 更新目标模型的评论数（增加）.
     */
    protected function incrementTargetCommentCount(Comment $comment): void
    {
        switch ($comment->target_type) {
            case 'post':
                \App\Modules\Post\Models\Post::where('id', $comment->target_id)->increment('comment_count');

                break;
            case 'article':
                \App\Modules\Article\Models\Article::where('id', $comment->target_id)->increment('comment_count');

                break;
                // 其它类型可按需扩展
        }
    }

    /**
     * 更新目标模型的评论数（减少）.
     */
    protected function decrementTargetCommentCount(Comment $comment): void
    {
        switch ($comment->target_type) {
            case 'post':
                \App\Modules\Post\Models\Post::where('id', $comment->target_id)
                    ->where('comment_count', '>', 0)
                    ->decrement('comment_count');

                break;
            case 'article':
                \App\Modules\Article\Models\Article::where('id', $comment->target_id)
                    ->where('comment_count', '>', 0)
                    ->decrement('comment_count');

                break;
                // 其它类型可按需扩展
        }
    }

    /**
     * 清除缓存.
     */
    protected function clearCommentCache(Comment $comment): void
    {
        Cache::forget("comments:{$comment->target_type}:{$comment->target_id}");
        if ($comment->parent_id) {
            Cache::forget("comments:replies:{$comment->parent_id}");
        }
    }
}
