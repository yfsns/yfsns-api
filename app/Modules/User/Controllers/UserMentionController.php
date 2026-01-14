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

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\UserMention;
use App\Modules\User\Requests\GetUserMentionsRequest;
use App\Modules\User\Requests\MarkMentionsAsReadRequest;
use App\Modules\User\Resources\UserMentionResource;
use App\Modules\User\Services\UserMentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 用户@管理
 *
 * @name 用户@管理
 */
class UserMentionController extends Controller
{
    protected UserMentionService $mentionService;

    public function __construct(UserMentionService $mentionService)
    {
        $this->mentionService = $mentionService;
    }

    /**
     * 获取用户的@记录列表
     *
     * @authenticated
     *
     * @queryParam content_type string 内容类型筛选：post, comment, message. Example: post
     * @queryParam status string 状态筛选：unread, read. Example: unread
     * @queryParam per_page int 每页数量，默认20. Example: 20
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "list": [
     *       {
     *         "id": 1,
     *         "sender": {
     *           "id": 2,
     *           "username": "sender",
     *           "nickname": "发送者",
     *           "avatar_url": "http://example.com/avatar.jpg"
     *         },
     *         "content_type": "post",
     *         "content_id": 123,
     *         "nickname_at_time": "发送者昵称",
     *         "status": "unread",
     *         "created_at": "2025-01-01T10:00:00.000000Z"
     *       }
     *     ],
     *     "total": 50,
     *     "current_page": 1,
     *     "per_page": 20
     *   }
     * }
     */
    public function index(GetUserMentionsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = auth()->id();

        $mentions = $this->mentionService->getUserMentions(
            $userId,
            $validated['per_page'] ?? 20,
            $validated['content_type'] ?? null,
            $validated['status'] ?? null
        );

        // 返回简洁的分页格式
        return response()->json([
            'data' => UserMentionResource::collection($mentions->items()),
            'current_page' => $mentions->currentPage(),
            'last_page' => $mentions->lastPage(),
            'per_page' => $mentions->perPage(),
            'total' => $mentions->total(),
            'from' => $mentions->firstItem(),
            'to' => $mentions->lastItem(),
            'prev_page_url' => $mentions->previousPageUrl(),
            'next_page_url' => $mentions->nextPageUrl(),
        ], 200);
    }

    /**
     * 获取用户的@统计信息
     *
     * @authenticated
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "total_mentions": 100,
     *     "unread_count": 15,
     *     "recent_mentions": 8
     *   }
     * }
     */
    public function stats(): JsonResponse
    {
        $userId = auth()->id();
        $stats = $this->mentionService->getUserMentionStats($userId);

        return response()->success($stats, '获取@统计成功');
    }

    /**
     * 标记单个@记录为已读
     *
     * @authenticated
     *
     * @urlParam mention int required @记录ID. Example: 1
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "@记录已标记为已读"
     * }
     * @response 404 {
     *   "code": 404,
     *   "message": "@记录不存在或无权限访问"
     * }
     */
    public function markAsRead(int $mentionId): JsonResponse
    {
        $userId = auth()->id();

        if ($this->mentionService->markAsRead($mentionId, $userId)) {
            return response()->success(null, '@记录已标记为已读');
        }

        return response()->error('@记录不存在或无权限访问', 404);
    }

    /**
     * 批量标记@记录为已读
     *
     * @authenticated
     *
     * @bodyParam mention_ids array required @记录ID数组. Example: [1,2,3]
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "批量标记成功",
     *   "data": {
     *     "marked_count": 3
     *   }
     * }
     */
    public function markAsReadBulk(MarkMentionsAsReadRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = auth()->id();

        $count = $this->mentionService->markAsReadBulk($validated['mention_ids'], $userId);

        return response()->json([
            'marked_count' => $count,
        ], 200);
    }

    /**
     * 标记所有@记录为已读
     *
     * @authenticated
     *
     * @queryParam content_type string 可选，指定内容类型. Example: post
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "标记成功",
     *   "data": {
     *     "marked_count": 15
     *   }
     * }
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $contentType = $request->query('content_type');

        $count = $this->mentionService->markAllAsRead($userId, $contentType);

        return response()->json([
            'marked_count' => $count,
        ], 200);
    }

    /**
     * 删除单个@记录
     *
     * @authenticated
     *
     * @urlParam mention int required @记录ID. Example: 1
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "@记录已删除"
     * }
     * @response 404 {
     *   "code": 404,
     *   "message": "@记录不存在或无权限访问"
     * }
     */
    public function destroy(int $mentionId): JsonResponse
    {
        $userId = auth()->id();

        if ($this->mentionService->deleteMention($mentionId, $userId)) {
            return response()->success(null, '@记录已删除');
        }

        return response()->error('@记录不存在或无权限访问', 404);
    }

    /**
     * 批量删除@记录
     *
     * @authenticated
     *
     * @bodyParam mention_ids array required @记录ID数组. Example: [1,2,3]
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "批量删除成功",
     *   "data": {
     *     "deleted_count": 3
     *   }
     * }
     */
    public function destroyBulk(MarkMentionsAsReadRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = auth()->id();

        $count = $this->mentionService->deleteMentionsBulk($validated['mention_ids'], $userId);

        return response()->json([
            'deleted_count' => $count,
        ], 200);
    }

    /**
     * 获取未读@数量
     *
     * @authenticated
     *
     * @queryParam content_type string 可选，指定内容类型. Example: post
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "unread_count": 15
     *   }
     * }
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $contentType = $request->query('content_type');

        $count = $this->mentionService->getUnreadCount($userId, $contentType);

        return response()->json([
            'unread_count' => $count,
        ], 200);
    }

    /**
     * 获取内容的@用户列表
     *
     * @authenticated
     *
     * @queryParam content_type string required 内容类型. Example: post
     * @queryParam content_id int required 内容ID. Example: 123
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": [
     *     {
     *       "id": 1,
     *       "sender": {
     *         "id": 2,
     *         "username": "sender",
     *         "nickname": "发送者"
     *       },
     *       "receiver": {
     *         "id": 3,
     *         "username": "receiver",
     *         "nickname": "接收者"
     *       },
     *       "content_type": "post",
     *       "nickname_at_time": "接收者昵称",
     *       "created_at": "2025-01-01T10:00:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function getContentMentions(Request $request): JsonResponse
    {
        $contentType = $request->query('content_type');
        $contentId = $request->query('content_id');

        if (!$contentType || !$contentId) {
            return response()->error('缺少必要参数', 400);
        }

        $mentions = $this->mentionService->getContentMentions($contentType, (int) $contentId);

        return response()->json(
            UserMentionResource::collection($mentions),
            200
        );
    }
}
