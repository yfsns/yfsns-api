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

namespace App\Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notification\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{

    /**
     * 获取未读通知数量.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知记录管理
     *
     * @authenticated
     *
     * @response {
     *   "code": 200,
     *   "message": "操作成功",
     *   "data": {
     *     "unreadCount": 5
     *   }
     * }
     */
    public function unreadCount(Request $request): JsonResponse
    {
            $count = $request->user()->unreadNotifications()->count();

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'unreadCount' => $count,
                ],
            ], 200);
    }

    /**
     * 获取通知列表.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知记录管理
     *
     * @authenticated
     *
     * @header Authorization Bearer {token}
     *
     * @queryParam per_page integer 每页数量，默认15
     * @queryParam cursor string 游标（用于无限滚动）
     * @queryParam limit integer 每次加载数量（用于无限滚动），默认15
     * @queryParam pagination_type string 分页类型（page-传统分页，cursor-游标分页），默认page
     *
     * @response {
     *   "data": [
     *     {
     *       "id": "1",
     *       "type": "App\\Notifications\\DatabaseNotification",
     *       "notifiable_type": "App\\Models\\User",
     *       "notifiable_id": 1,
     *       "data": {
     *         "message": "欢迎 user1，注册成功！"
     *       },
     *       "read_at": null,
     *       "created_at": "2024-03-20T00:00:00.000000Z",
     *       "updated_at": "2024-03-20T00:00:00.000000Z"
     *     }
     *   ],
     *   "current_page": 1,
     *   "per_page": 15,
     *   "total": 1
     * }
     */
    public function index(Request $request): JsonResponse
    {
            $paginationType = $request->input('pagination_type', 'page');

            if ($paginationType === 'cursor') {
                return $this->getNotificationsByCursor($request);
            }

            // 传统分页
            $notifications = $request->user()
                ->notifications()
                ->orderByDesc('created_at')
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $this->formatPaginationData($notifications, NotificationResource::class)
            ]);
    }

    /**
     * 获取通知详情.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知记录管理
     *
     * @authenticated
     *
     * @header Authorization Bearer {token}
     *
     * @response {
     *   "id": "1",
     *   "type": "App\\Notifications\\DatabaseNotification",
     *   "notifiable_type": "App\\Models\\User",
     *   "notifiable_id": 1,
     *   "data": {
     *     "message": "欢迎 user1，注册成功！"
     *   },
     *   "read_at": null,
     *   "created_at": "2024-03-20T00:00:00.000000Z",
     *   "updated_at": "2024-03-20T00:00:00.000000Z"
     * }
     */
    public function show(string $id): JsonResponse
    {
            // 手动查找通知
            $notification = DatabaseNotification::find($id);

            if (! $notification) {
                return response()->json([
                    'code' => 404,
                    'message' => '通知不存在',
                    'data' => null,
                ], 404);
            }

            // 验证用户是否有权限查看此通知
            $currentUser = auth()->user();

            if (! $currentUser || $notification->notifiable_id != $currentUser->id) {
                return response()->json([
                    'code' => 403,
                    'message' => '无权访问此通知',
                    'data' => null,
                ], 403);
            }

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => new NotificationResource($notification),
            ], 200);
    }

    /**
     * 删除通知.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知记录管理
     *
     * @authenticated
     *
     * @header Authorization Bearer {token}
     *
     * @response {
     *   "message": "通知已删除"
     * }
     */
    public function destroy(string $id): JsonResponse
    {
            // 手动查找通知
            $notification = DatabaseNotification::find($id);

            if (! $notification) {
                return response()->json([
                    'code' => 404,
                    'message' => '通知不存在',
                    'data' => null,
                ], 404);
            }

            // 验证用户是否有权限删除此通知
            $currentUser = auth()->user();
            if (! $currentUser || $notification->notifiable_id != $currentUser->id) {
                return response()->json([
                    'code' => 403,
                    'message' => '无权删除此通知',
                    'data' => null,
                ], 403);
            }

            $notification->delete();

            return response()->json([
                'code' => 200,
                'message' => '通知已删除',
                'data' => null,
            ], 200);
    }

    /**
     * 标记通知为已读.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知记录管理
     *
     * @authenticated
     *
     * @header Authorization Bearer {token}
     *
     * @response {
     *   "message": "通知已标记为已读"
     * }
     */
    public function markAsRead(string $id): JsonResponse
    {
            // 手动查找通知
            $notification = DatabaseNotification::find($id);

            if (! $notification) {
                return response()->json([
                    'code' => 404,
                    'message' => '通知不存在',
                    'data' => null,
                ], 404);
            }

            // 验证用户是否有权限操作此通知
            $currentUser = auth()->user();
            if (! $currentUser || $notification->notifiable_id != $currentUser->id) {
                return response()->json([
                    'code' => 403,
                    'message' => '无权操作此通知',
                    'data' => null,
                ], 403);
            }

            // 直接更新数据库，避免Laravel的markAsRead()方法问题
            $notification->update(['read_at' => now()]);

            return response()->json([
                'code' => 200,
                'message' => '通知已标记为已读',
                'data' => null,
            ], 200);
    }

    /**
     * 标记所有通知为已读.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知记录管理
     *
     * @authenticated
     *
     * @header Authorization Bearer {token}
     *
     * @response {
     *   "message": "所有通知已标记为已读"
     * }
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
            $request->user()->unreadNotifications->markAsRead();

            return response()->json([
                'code' => 200,
                'message' => '所有通知已标记为已读',
                'data' => null,
            ], 200);
    }

    /**
     * 获取全部通知列表（仅管理员）.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知记录管理
     *
     * @authenticated
     *
     * @header Authorization Bearer {token}
     *
     * @queryParam per_page integer 每页数量，默认15
     *
     * @response {
     *   "data": [],
     *   "current_page": 1,
     *   "per_page": 15,
     *   "total": 0,
     *   "last_page": 1,
     *   "from": null,
     *   "to": null
     * }
     */
    public function all(Request $request): JsonResponse
    {
            // 这里建议加管理员权限判断
            $notifications = DatabaseNotification::orderByDesc('created_at')
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $this->formatPaginationData($notifications, NotificationResource::class)
            ]);
    }

    /**
     * 获取当前用户的通知列表.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知记录管理
     *
     * @authenticated
     *
     * @header Authorization Bearer {token}
     *
     * @queryParam per_page integer 每页数量，默认15
     * @queryParam cursor string 游标（用于无限滚动）
     * @queryParam limit integer 每次加载数量（用于无限滚动），默认15
     * @queryParam pagination_type string 分页类型（page-传统分页，cursor-游标分页），默认page
     *
     * @response {
     *   "data": [
     *     {
     *       "id": "1",
     *       "type": "App\\Notifications\\DatabaseNotification",
     *       "notifiable_type": "App\\Models\\User",
     *       "notifiable_id": 1,
     *       "data": {
     *         "message": "欢迎 user1，注册成功！"
     *       },
     *       "read_at": null,
     *       "created_at": "2024-03-20T00:00:00.000000Z",
     *       "updated_at": "2024-03-20T00:00:00.000000Z"
     *     }
     *   ],
     *   "current_page": 1,
     *   "per_page": 15,
     *   "total": 1
     * }
     */
    public function userList(Request $request): JsonResponse
    {
            $paginationType = $request->input('pagination_type', 'page');

            if ($paginationType === 'cursor') {
                return $this->getNotificationsByCursor($request);
            }

            // 传统分页
            $notifications = $request->user()
                ->notifications()
                ->orderByDesc('created_at')
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $this->formatPaginationData($notifications, NotificationResource::class)
            ]);
    }

    /**
     * 使用游标分页获取通知列表（支持无限滚动）.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function getNotificationsByCursor(Request $request): JsonResponse
    {
            $limit = $request->input('limit', 15);
            $cursor = $request->input('cursor');

            $query = $request->user()
                ->notifications()
                ->orderByDesc('created_at');

            // 应用游标分页
            if ($cursor) {
                $query->where('id', '<', $cursor);
            }

            // 获取数据（多取一条用于判断是否还有更多）
            $notifications = $query->limit($limit + 1)->get();

            // 检查是否还有更多数据
            $hasMore = $notifications->count() > $limit;
            if ($hasMore) {
                $notifications = $notifications->take($limit);
            }

            // 安全获取 nextCursor（如果集合为空则返回 null）
            $nextCursor = null;
            if ($hasMore && $notifications->isNotEmpty()) {
                $nextCursor = $notifications->last()->id;
            }

            // 使用统一的响应格式
            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'list' => NotificationResource::collection($notifications),
                    'hasMore' => $hasMore,
                    'nextCursor' => $nextCursor,
                    'limit' => $limit,
                    'paginationType' => 'cursor',
                ],
            ], 200);
    }
}
