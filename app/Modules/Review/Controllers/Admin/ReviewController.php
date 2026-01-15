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

namespace App\Modules\Review\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Review\Requests\Admin\GetLogsRequest;
use App\Modules\Review\Requests\Admin\ManualReviewRequest;
use App\Modules\Review\Services\ReviewService;
use Illuminate\Http\Request;

use function count;

use Illuminate\Http\JsonResponse;

/**
 * 审核控制器.
 *
 * 提供人工审核功能和审核日志查询
 * 支持审核不同类型的内容（article、post、thread、comment）
 */
class ReviewController extends Controller
{
    protected ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * 人工审核.
     *
     * @param null|int $id 路径参数中的ID（可选，如果提供则优先使用）
     */
    public function manualReview(ManualReviewRequest $request, ?int $id = null): JsonResponse
    {
        $data = $request->validated();

        // 如果路径中有ID，优先使用路径参数
        if ($id) {
            $data['content_id'] = $id;
        }

        // 确保 content_id 存在
        if (empty($data['content_id'])) {
            return response()->json([
                'code' => 400,
                'message' => '内容ID不能为空',
                'data' => null,
            ], 400);
        }

        // content_type 必须提供，无法仅从ID推断
        if (empty($data['content_type'])) {
            return response()->json([
                'code' => 400,
                'message' => '内容类型（content_type）不能为空，请指定 article、post、thread 或 comment',
                'data' => null,
            ], 400);
        }

        // 获取内容对象
        $reviewable = $this->getReviewable($data['content_type'], $data['content_id']);

        if (! $reviewable) {
            return response()->json([
                'code' => 404,
                'message' => '内容不存在',
                'data' => null,
            ], 404);
        }

        // 执行人工审核 - 将前端状态转换为action
        $action = match ($data['status']) {
            'published', 'approved' => 'approve',
            'rejected' => 'reject',
            'pending' => 'pending',
            default => throw new \InvalidArgumentException("不支持的状态值: {$data['status']}"),
        };

        $log = $this->reviewService->manualReview(
            $reviewable,
            $action,
            $data['remark'] ?? null,
            auth()->id(),
            $data['extra_data'] ?? null
        );

        return response()->json([
            'code' => 200,
            'message' => '审核完成',
            'data' => [
                'log' => $log,
                'content' => $reviewable->fresh(),
            ],
        ], 200);
    }


    /**
     * 获取审核记录.
     */
    public function getLogs(GetLogsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $reviewable = $this->getReviewable($data['content_type'], $data['content_id']);

        if (! $reviewable) {
            return response()->json([
                'code' => 404,
                'message' => '内容不存在',
                'data' => null,
            ], 404);
        }

        $logs = $this->reviewService->getLogs(
            $reviewable,
            $data['channel'] ?? null
        );

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $logs,
        ], 200);
    }

    /**
     * 批量审核
     */
    public function batchManualReview(Request $request): JsonResponse
    {
        $request->validate([
            'reviews' => 'required|array',
            'reviews.*.content_type' => 'required|string',
            'reviews.*.content_id' => 'required|integer',
            'reviews.*.action' => 'required|in:approve,reject',
            'reviews.*.remark' => 'nullable|string',
            'reviews.*.extra_data' => 'nullable|array',
        ]);

        $reviews = [];
        foreach ($request->reviews as $reviewData) {
            $model = $this->getReviewable($reviewData['content_type'], $reviewData['content_id']);
            if (!$model) {
                continue; // 跳过不存在的内容
            }

            $reviews[] = [
                'model' => $model,
                'action' => $reviewData['action'],
                'remark' => $reviewData['remark'] ?? null,
                'extra_data' => $reviewData['extra_data'] ?? null,
            ];
        }

        $results = $this->reviewService->batchManualReview($reviews, auth()->id());

        return response()->json([
            'code' => 200,
            'message' => '批量审核完成',
            'data' => $results,
        ], 200);
    }

    /**
     * 获取审核统计信息
     */
    public function getStats(Request $request): JsonResponse
    {
        $request->validate([
            'content_type' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $stats = $this->reviewService->getReviewStats(
            $request->content_type,
            $request->date_from,
            $request->date_to
        );

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $stats,
        ], 200);
    }

    /**
     * 获取待审核内容列表（管理界面使用）
     */
    public function getPendingContents(Request $request): JsonResponse
    {
        $request->validate([
            'content_type' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
        ]);

        $contents = $this->reviewService->getPendingContents(
            $request->content_type,
            $request->limit ?? 50,
            $request->offset ?? 0
        );

        $counts = $this->reviewService->getPendingCount($request->content_type);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'contents' => $contents,
                'counts' => $counts,
            ],
        ], 200);
    }

    /**
     * 获取审核对象
     */
    protected function getReviewable(string $contentType, int $contentId)
    {
        return match ($contentType) {
            'article' => \App\Modules\Article\Models\Article::find($contentId),
            'post' => \App\Modules\Post\Models\Post::find($contentId),
            'thread' => \App\Modules\Forum\Models\ForumThread::find($contentId),
            'comment' => \App\Modules\Comment\Models\Comment::find($contentId),
            default => null,
        };
    }

}
