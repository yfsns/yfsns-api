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

namespace App\Modules\Post\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Post\Models\Post;
use App\Modules\Post\Requests\StoreVideoRequest;
use App\Modules\Post\Requests\GetVideosRequest;
use App\Modules\Post\Requests\GetVideoDetailRequest;
use App\Modules\Post\Resources\VideoResource;
use App\Modules\Post\Services\PostService;
use App\Modules\Post\Services\VideoService;
use App\Modules\User\Services\UserService;
use Illuminate\Http\JsonResponse;

/**
 * @group 视频管理
 *
 * @name 视频管理
 */
class VideoController extends Controller
{
    protected VideoService $videoService;
    protected PostService $postService;
    protected UserService $userService;

    public function __construct(VideoService $videoService, PostService $postService, UserService $userService)
    {
        $this->videoService = $videoService;
        $this->postService = $postService;
        $this->userService = $userService;
    }

    /**
     * 创建视频
     *
     * @authenticated
     *
     * @bodyParam title string required 视频标题，最多255字符. Example: 我的精彩视频
     * @bodyParam content string 视频描述，最大2000字符. Example: 这是一个很棒的视频...
     * @bodyParam visibility int required 可见性：1=公开,2=好友可见,3=私密,4=粉丝可见. Example: 1
     * @bodyParam file_ids array required 视频文件ID数组，至少1个，最多5个. Example: [1,2]
     * @bodyParam location object 地理位置信息. Example: {"latitude":39.9042,"longitude":116.4074,"address":"北京市朝阳区"}
     * @bodyParam mentions array @用户ID数组，最多20个. Example: [1,2,3]
     * @bodyParam topics array 话题ID数组，最多10个. Example: [10,20]
     *
     * @response 201 {
     *   "code": 201,
     *   "message": "视频发布成功",
     *   "data": {
     *     "id": "1",
     *     "type": "video",
     *     "title": "我的精彩视频",
     *     "contentHtml": "这是一个很棒的视频...",
     *     "status": 1,
     *     "created_at": "2025-12-23T08:53:48.000000Z"
     *   }
     * }
     * @response 422 {
     *   "code": 422,
     *   "message": "验证失败",
     *   "errors": {
     *     "file_ids": ["至少需要上传一个视频"]
     *   }
     * }
     */
    public function store(StoreVideoRequest $request): JsonResponse
    {
        // 检查创建权限
        $this->authorize('create', Post::class);

        $data = $request->validated();
        $data['type'] = Post::TYPE_VIDEO; // 设置类型为视频
        $data['user_id'] = $this->userService->getCurrentUserId();

        $video = $this->videoService->create($data, $request);

        return response()->json([
            'code' => 201,
            'message' => '视频发布成功',
            'data' => new VideoResource($video),
        ], 201);
    }

    /**
     * 获取视频列表
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "data": [
     *       {
     *         "id": "1",
     *         "type": "video",
     *         "title": "视频标题",
     *         "contentHtml": "视频描述",
     *         "videos": [...],
     *         "author": {...}
     *       }
     *     ],
     *     "next_cursor": "xxx",
     *     "has_more": true
     *   }
     * }
     */
    public function index(GetVideosRequest $request): JsonResponse
    {
        // 获取验证后的数据（已经是下划线格式）
        $validated = $request->validated();

        $params = [
            'type' => 'video', // 视频固定为video类型
            'filter' => $validated['filter'] ?? 'all',
            'userId' => $validated['user_id'] ?? null,
            'topicId' => $validated['topic_id'] ?? null,
            'topicName' => $validated['topicName'] ?? null,
            'cursor' => $validated['cursor'] ?? null,
            'limit' => $validated['limit'] ?? 10,
            'page' => $validated['page'] ?? null,
        ];

        // 根据筛选类型验证必要参数
        if ($params['filter'] === 'user' && ! $params['userId']) {
            return response()->json([
                'code' => 400,
                'message' => '获取用户视频时必须提供userId参数',
                'data' => null,
            ], 400);
        }

        if ($params['filter'] === 'topic' && ! ($params['topicId'] ?? $validated['topicName'] ?? null)) {
            return response()->json([
                'code' => 400,
                'message' => '按话题筛选时必须提供topicId或topicName参数',
                'data' => null,
            ], 400);
        }

        if (in_array($params['filter'], ['liked', 'my', 'following']) && ! auth()->check()) {
            return response()->json([
                'code' => 401,
                'message' => '需要登录才能访问此内容',
                'data' => null,
            ], 401);
        }

        $result = $this->postService->getUnifiedPosts($params);

        // 返回cursor分页结果（保持现有壳结构）
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $result,
        ], 200);
    }

    /**
     * 获取视频详情
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "id": "1",
     *     "type": "video",
     *     "title": "视频标题",
     *     "contentHtml": "视频描述",
     *     "videos": [...],
     *     "author": {...}
     *   }
     * }
     * @response 404 {
     *   "code": 404,
     *   "message": "视频不存在"
     * }
     */
    public function show(Post $post, GetVideoDetailRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $requestType = 'video'; // 视频固定为video类型

        // 验证是否为视频类型
        if ($post->type !== $requestType) {
            return response()->json([
                'code' => 404,
                'message' => '视频不存在',
                'data' => null,
            ], 404);
        }

        // 检查查看权限
        $this->authorize('view', $post);

        // 加载视频详情所需的关联数据
        $post->load([
            'user' => function ($query) {
                $query->withEssentialFields('status');
            },
            'files:id,name,path,type,size,mime_type,storage,thumbnail,created_at,updated_at',
            'likes:id,likeable_id,user_id',
            'collects:id,collectable_id,user_id',
            'comments:id,target_id,user_id,content,created_at',
            'mentions:id,sender_id,receiver_id,username,nickname_at_time',
            'mentions.receiver' => function ($query) {
                $query->selectBasic();
            },
            'topics:id,name,description,cover,post_count,follower_count',
        ]);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => new VideoResource($post),
        ], 200);
    }

    /**
     * 删除视频
     *
     * @authenticated
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "视频删除成功"
     * }
     * @response 404 {
     *   "code": 404,
     *   "message": "视频不存在"
     * }
     */
    public function destroy(Post $post): JsonResponse
    {
        // 验证是否为视频类型
        if ($post->type !== Post::TYPE_VIDEO) {
            return response()->json([
                'code' => 404,
                'message' => '视频不存在',
                'data' => null,
            ], 404);
        }

        // 检查删除权限
        $this->authorize('delete', $post);

        $this->videoService->delete($post->id);

        return response()->json([
            'code' => 200,
            'message' => '视频删除成功',
            'data' => null,
        ], 200);
    }
}