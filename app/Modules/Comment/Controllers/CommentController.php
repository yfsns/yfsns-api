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

namespace App\Modules\Comment\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Comment\Models\Comment;
use App\Modules\Comment\Requests\GetCommentRepliesRequest;
use App\Modules\Comment\Requests\GetCommentsRequest;
use App\Modules\Comment\Requests\StoreCommentRequest;
use App\Modules\Comment\Requests\ToggleLikeRequest;
use App\Modules\Comment\Resources\CommentResource;
use App\Modules\Comment\Services\UserCommentService;
use App\Modules\User\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @group 评论管理
 *
 * @name 评论管理
 */
class CommentController extends Controller
{

    protected $service;
    protected $userService;

    public function __construct(UserCommentService $service, UserService $userService)
    {
        $this->service = $service;
        $this->userService = $userService;
    }

    /**
     * 发表评论
     *
     * @authenticated
     *
     * @bodyParam target_id int required 目标ID（文章ID或帖子ID）. Example: 123
     * @bodyParam target_type string required 目标类型：post（帖子）或article（文章）. Example: post
     * @bodyParam content string 与images、video_url至少提供一项，最大1000字符. Example: 这篇文章写得很好！
     * @bodyParam content_type string required 内容类型：text（文本）、image（图片）或video（视频）. Example: text
     * @bodyParam images array 图片链接数组，最多9张，与content、video_url至少提供一项. Example: ["https://example.com/image1.jpg", "https://example.com/image2.png"]
     * @bodyParam video_url string 视频链接，与content、images至少提供一项. Example: https://example.com/video.mp4
     * @bodyParam parent_id int 父评论ID，用于回复评论，可选. Example: 456
     * @bodyParam mentions array @用户ID数组，最多10个. Example: [1, 2, 3]
     * @bodyParam topics array 关联话题ID数组，最多5个. Example: [10, 20]
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "评论发表成功",
     *   "data": {
     *     "id": 1,
     *     "content": "这篇文章写得很好！",
     *     "content_type": "text",
     *     "status": 1,
     *     "created_at": "2025-12-23T08:53:48.000000Z"
     *   }
     * }
     * @response 422 {
     *   "code": 422,
     *   "message": "验证失败",
     *   "errors": {
     *     "content": ["文本内容、图片或视频至少要提供一项"]
     *   }
     * }
     */
    public function store(StoreCommentRequest $request): JsonResponse
    {
        // 检查创建评论权限
        $this->authorize('create', \App\Modules\Comment\Models\Comment::class);

        $data = $request->validated();
        $data['user_id'] = $this->userService->getCurrentUserId();

        $comment = $this->service->create($data, $request);

        return response()->json([
            'code' => 201,
            'message' => '评论发表成功',
            'data' => new CommentResource($comment),
        ], 201);
    }

    /**
     * 删除评论.
     *
     * @authenticated
     *
     * @urlParam id int required 评论ID. Example: 1
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "删除成功"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        $comment = \App\Modules\Comment\Models\Comment::findOrFail($id);

        // 检查删除评论权限
        $this->authorize('delete', $comment);

        // 使用服务类的删除接口
        $this->service->deleteComment($comment);

        return response()->json([
            'code' => 200,
            'message' => '删除成功',
            'data' => null,
        ], 200);
    }

    /**
     * 点赞/取消点赞评论.
     *
     * @authenticated
     *
     * @urlParam id int required 评论ID. Example: 1
     *
     * @queryParam action string required 操作类型（like-点赞、unlike-取消点赞）. Example: like
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "操作成功"
     * }
     */
    public function toggleLike(ToggleLikeRequest $request, int $id): JsonResponse
    {
        $comment = \App\Modules\Comment\Models\Comment::findOrFail($id);

        // 检查点赞权限
        $this->authorize('like', $comment);

        $validated = $request->validated();
        $action = $validated['action'] ?? 'like';

        [$method, $message] = match ($action) {
            'unlike' => ['unlike', '取消点赞成功'],
            default => ['like', '点赞成功'],
        };

        $this->service->$method($id, $this->userService->getCurrentUserId());

        return response()->json([
            'code' => 200,
            'message' => $message,
            'data' => null,
        ], 200);
    }

    /**
     * 获取评论树（包含主评论和所有回复）.
     *
     * 返回指定目标的完整评论树结构，主评论包含其所有回复。
     * 前端只需传递 target_id 和 target_type。
     *
     * 使用方式：
     * GET /api/v1/comments?target_id=1&target_type=post
     *
     * @authenticated
     *
     * @queryParam target_id int required 目标ID（如动态ID、文章ID等）. Example: 1
     * @queryParam target_type string required 目标类型（post-动态、article-文章等）. Example: post
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": [
     *     {
     *       "id": 1,
     *       "parentId": null,
     *       "content": "主评论内容",
     *       "replies": [
     *         {
     *           "id": 2,
     *           "parentId": 1,
     *           "content": "回复内容"
     *         }
     *       ]
     *     }
     *   ]
     * }
     */
    public function index(GetCommentsRequest $request): JsonResponse
    {
        $params = $request->validated();

        // 获取分页的评论树
        $paginator = $this->service->getPagedCommentTree($params, $request->user());

        // 提取分页元数据（在转换资源之前，不返回 path 避免暴露内部域名）
        $paginationData = [
            // 'path' => $paginator->path(),
            'per_page' => $paginator->perPage(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_cursor' => $paginator->previousCursor()?->encode(),
            'prev_page_url' => $paginator->previousPageUrl(),
        ];

        // 转换数据为资源格式
        $data = CommentResource::collection($paginator->items());

        // 返回cursor分页结果（保持壳结构）
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => array_merge($paginationData, [
                'data' => $data,
            ]),
        ], 200);
    }

    /**
     * 获取评论回复列表（游标分页）.
     *
     * @authenticated
     *
     * @urlParam commentId int required 评论ID. Example: 1
     *
     * @queryParam limit int 每次加载数量，默认10. Example: 10
     * @queryParam cursor int 游标（上一页最后一条回复的id）. Example: 100
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "list": [...],
     *     "hasMore": true,
     *     "nextCursor": 80
     *   }
     * }
     */
    public function replies(GetCommentRepliesRequest $request, int $commentId): JsonResponse
    {
        $params = $request->validated();

        $currentUserId = auth()->id();

        // 直接返回评论回复列表（暂时不分页）
        $replies = Comment::query()
            ->with([
                'user' => function ($query) {
                    $query->withEssentialFields('status');
                },
                // 只加载当前用户的点赞状态（如果已登录）
                'likes' => function ($query) use ($currentUserId) {
                    if ($currentUserId) {
                        $query->where('user_id', $currentUserId)
                              ->select('id', 'likeable_id', 'user_id');
                    } else {
                        // 未登录用户不加载任何点赞数据
                        $query->whereRaw('1 = 0');
                    }
                },
                'mentions:id,sender_id,receiver_id,username,nickname_at_time',
                'mentions.user' => function ($query) {
                    $query->withBasicFields();
                },
                'topics:id,name,description,cover,post_count,follower_count',
            ])
            ->replies($commentId)
            ->published()
            ->orderBy('created_at', 'asc')
            ->get();

        if ($replies->isEmpty()) {
            return response()->json([
                'code' => 200,
                'message' => '暂无回复数据',
                'data' => [],
            ], 200);
        }

        $items = CommentResource::collection($replies);
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $items,
        ], 200);
    }
}
