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

namespace App\Modules\Review\Traits;

use App\Modules\Review\Models\ReviewLog;
use App\Modules\Review\Services\ReviewService;
use App\Modules\System\Services\ContentReviewConfigService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 审核功能Trait
 *
 * 为模型提供审核相关功能，支持多态关联审核日志
 */
trait HasReviewable
{
    /**
     * 获取审核日志关联.
     */
    public function reviewLogs(): MorphMany
    {
        return $this->morphMany(ReviewLog::class, 'reviewable');
    }

    /**
     * 获取最新的审核日志.
     */
    public function latestReviewLog(): ?ReviewLog
    {
        return $this->reviewLogs()->latest()->first();
    }

    /**
     * 获取审核状态.
     */
    public function getReviewStatus(): ?string
    {
        return $this->status;
    }

    /**
     * 是否已审核通过.
     */
    public function isApproved(): bool
    {
        $status = $this->getReviewStatus();
        return $this->isApprovedStatus($status);
    }

    /**
     * 是否已审核拒绝.
     */
    public function isRejected(): bool
    {
        $status = $this->getReviewStatus();
        return $this->isRejectedStatus($status);
    }

    /**
     * 是否待审核.
     */
    public function isPending(): bool
    {
        $status = $this->getReviewStatus();
        return $this->isPendingStatus($status);
    }

    /**
     * 判断状态是否为审核通过.
     */
    protected function isApprovedStatus($status): bool
    {
        $class = get_class($this);

        // Post, Comment, Topic用数字状态
        if (str_contains($class, 'Post') || str_contains($class, 'Comment') || str_contains($class, 'Topic')) {
            return $status == 1; // published
        }

        // Article, ForumThread用字符串状态
        return $status === 'published';
    }

    /**
     * 判断状态是否为审核拒绝.
     */
    protected function isRejectedStatus($status): bool
    {
        $class = get_class($this);

        // Post, Comment, Topic用数字状态
        if (str_contains($class, 'Post') || str_contains($class, 'Comment') || str_contains($class, 'Topic')) {
            return $status == 2; // rejected
        }

        // Article, ForumThread用字符串状态
        return $status === 'rejected';
    }

    /**
     * 判断状态是否为待审核.
     */
    protected function isPendingStatus($status): bool
    {
        $class = get_class($this);

        // Post, Comment, Topic用数字状态
        if (str_contains($class, 'Post') || str_contains($class, 'Comment') || str_contains($class, 'Topic')) {
            return $status == 0; // pending
        }

        // Article, ForumThread用字符串状态
        return $status === 'pending';
    }

    /**
     * 审核通过.
     */
    public function approve(?string $remark = null, ?int $adminId = null, ?array $extraData = null): ReviewLog
    {
        return app(ReviewService::class)->manualReview($this, 'approve', $remark, $adminId, $extraData);
    }

    /**
     * 审核拒绝.
     */
    public function reject(?string $remark = null, ?int $adminId = null, ?array $extraData = null): ReviewLog
    {
        return app(ReviewService::class)->manualReview($this, 'reject', $remark, $adminId, $extraData);
    }

    /**
     * 获取审核统计.
     */
    public function getReviewStats(): array
    {
        $logs = $this->reviewLogs()->get();

        return [
            'total_reviews' => $logs->count(),
            'approved_count' => $logs->where('new_status', $this->isApprovedStatus(1) ? '1' : 'published')->count(),
            'rejected_count' => $logs->where('new_status', $this->isRejectedStatus(2) ? '2' : 'rejected')->count(),
            'latest_review' => $logs->sortByDesc('created_at')->first(),
        ];
    }

    /**
     * 检查内容审核是否开启.
     */
    public function isContentReviewEnabled(): bool
    {
        try {
            /** @var ContentReviewConfigService $service */
            $service = app(ContentReviewConfigService::class);
            return $service->isEnabled($this->getModuleName());
        } catch (Throwable $e) {
            Log::warning('读取内容审核配置失败', [
                'module' => $this->getModuleName(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 获取模块名称.
     */
    abstract protected function getModuleName(): string;


    /**
     * 执行人工审核内容.
     *
     * @param string $action 审核动作：'approve' 或 'reject'
     * @param string|null $remark 审核备注/原因
     * @param array|null $extraData 扩展数据
     * @return ReviewLog
     */
    public function manualReview(
        string $action,
        ?string $remark = null,
        ?array $extraData = null
    ): ReviewLog {
        return app(ReviewService::class)->manualReview(
            $this,
            $action,
            $remark,
            auth()->id(),
            $extraData
        );
    }

    /**
     * 批量人工审核内容.
     *
     * @param array $reviews 审核数据数组
     * @return array
     */
    public static function batchManualReview(array $reviews): array
    {
        return app(ReviewService::class)->batchManualReview($reviews, auth()->id());
    }
}
