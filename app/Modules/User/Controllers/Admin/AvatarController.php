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

namespace App\Modules\User\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\UserAsset;
use App\Modules\User\Requests\Admin\ReviewAvatarRequest;
use App\Modules\User\Services\AvatarReviewService;
use Illuminate\Http\JsonResponse;

/**
 * 管理员头像审核控制器
 * 处理头像审核相关管理功能.
 */
class AvatarController extends Controller
{
    protected AvatarReviewService $avatarService;

    public function __construct(AvatarReviewService $avatarService)
    {
        $this->avatarService = $avatarService;
    }

    /**
     * 获取待审核头像列表.
     */
    public function pendingReviews(): JsonResponse
    {
        $filters = request()->only(['user_id', 'date_from', 'date_to', 'per_page']);

        $reviews = $this->avatarService->getPendingReviews($filters);

        $data = $reviews->map(function ($asset) {
            return [
                'id' => $asset->id,
                'user' => [
                    'id' => $asset->user->id,
                    'username' => $asset->user->username,
                    'nickname' => $asset->user->nickname,
                ],
                'avatar_url' => $asset->getFullUrl(),
                'submitted_at' => $asset->created_at,
                'review_attempts' => $asset->review_attempts,
                'ai_review_result' => $asset->extra['ai_review_result'] ?? null,
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => $data,
                'pagination' => [
                    'total' => $reviews->total(),
                    'per_page' => $reviews->perPage(),
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                ],
            ],
        ], 200);
    }

    /**
     * 审核头像.
     */
    public function review(ReviewAvatarRequest $request, UserAsset $asset): JsonResponse
    {
        // 验证资产是否为头像且待审核
        if ($asset->type !== UserAsset::TYPE_AVATAR) {
            return response()->json([
                'code' => 400,
                'message' => '只能审核头像文件',
                'data' => null,
            ], 400);
        }

        if (!$asset->isPending()) {
            return response()->json([
                'code' => 400,
                'message' => '该头像已审核完成',
                'data' => null,
            ], 400);
        }

        $reviewer = auth()->user();

        if ($request->action === 'approve') {
            $this->avatarService->approveAvatar($asset, $reviewer, $request->remark ?? '');
            return response()->json([
                'code' => 200,
                'message' => '头像审核通过',
                'data' => [],
            ], 200);
        } elseif ($request->action === 'reject') {
            $this->avatarService->rejectAvatar($asset, $reviewer, $request->remark ?? '');
            return response()->json([
                'code' => 200,
                'message' => '头像审核拒绝',
                'data' => [],
            ], 200);
        } else {
            return response()->json([
                'code' => 400,
                'message' => '无效的审核操作',
                'data' => null,
            ], 400);
        }
    }

    /**
     * 获取审核统计信息.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'pending_count' => UserAsset::where('type', UserAsset::TYPE_AVATAR)
                ->where('review_status', UserAsset::REVIEW_PENDING)
                ->count(),

            'approved_today' => UserAsset::where('type', UserAsset::TYPE_AVATAR)
                ->where('review_status', UserAsset::REVIEW_APPROVED)
                ->whereDate('reviewed_at', today())
                ->count(),

            'rejected_today' => UserAsset::where('type', UserAsset::TYPE_AVATAR)
                ->where('review_status', UserAsset::REVIEW_REJECTED)
                ->whereDate('reviewed_at', today())
                ->count(),
        ];

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $stats,
        ], 200);
    }
}
