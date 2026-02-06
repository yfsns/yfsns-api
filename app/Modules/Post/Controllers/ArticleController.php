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
use App\Modules\Post\Requests\StoreArticleRequest;
use App\Modules\Post\Requests\GetArticlesRequest;
use App\Modules\Post\Requests\GetArticleDetailRequest;
use App\Modules\Post\Resources\ArticleResource;
use App\Modules\Post\Services\ArticleService;
use App\Modules\Post\Services\PostService;
use App\Modules\User\Services\UserService;
use Illuminate\Http\JsonResponse;

/**
 * @group 文章管理
 *
 * @name 文章管理
 */
class ArticleController extends Controller
{
    protected ArticleService $articleService;
    protected PostService $postService;
    protected UserService $userService;

    public function __construct(ArticleService $articleService, PostService $postService, UserService $userService)
    {
        $this->articleService = $articleService;
        $this->postService = $postService;
        $this->userService = $userService;
    }

    /**
     * 获取文章列表
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "data": [
     *       {
     *         "id": "1",
     *         "type": "article",
     *         "title": "文章标题",
     *         "contentHtml": "文章内容",
     *         "excerpt": "文章摘要...",
     *         "author": {...}
     *       }
     *     ],
     *     "next_cursor": "xxx",
     *     "has_more": true
     *   }
     * }
     */
    public function index(GetArticlesRequest $request): JsonResponse
    {
        // 获取验证后的数据（已经是下划线格式）
        $validated = $request->validated();

        $params = [
            'type' => 'article', // 文章固定为article类型
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
                'message' => '获取用户文章时必须提供userId参数',
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
     * 获取文章详情
     *
     * @authenticated
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "id": "1",
     *     "type": "article",
     *     "title": "文章标题",
     *     "contentHtml": "文章内容",
     *     "excerpt": "文章摘要...",
     *     "status": 1,
     *     "statusText": "已发布",
     *     "visibility": 1,
     *     "visibilityText": "公开",
     *     "author": {
     *       "id": "1",
     *       "username": "admin",
     *       "nickname": "超级管理员",
     *       "avatarUrl": "http://localhost:8000/storage/uploads/user/2026/02/04/1_1770209953_f1545cb6.jpeg"
     *     },
     *     "createdAtHuman": "1周前",
     *     "readingTime": 3,
     *     "wordCount": 800,
     *     "likeCount": 0,
     *     "commentCount": 14,
     *     "collectCount": 0,
     *     "isLiked": false,
     *     "isCollected": false,
     *     "canEdit": true,
     *     "canDelete": true,
     *     "images": [],
     *     "coverImage": null
     *   }
     * }
     */
    public function show(Post $post, GetArticleDetailRequest $request): JsonResponse
    {
        // 验证是否为文章类型
        if ($post->type !== Post::TYPE_ARTICLE) {
            return response()->json([
                'code' => 404,
                'message' => '文章不存在',
                'data' => null,
            ], 404);
        }

        // 检查查看权限
        $this->authorize('view', $post);

        // 加载文章关联数据
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
            'data' => new ArticleResource($post),
        ], 200);
    }

    /**
     * 创建文章
     *
     * @authenticated
     *
     * @bodyParam title string required 文章标题，最多255字符. Example: 我的第一篇文章
     * @bodyParam content string required 文章内容，最大50000字符. Example: 这是文章的内容...
     * @bodyParam visibility int required 可见性：1=公开,2=好友可见,3=私密,4=粉丝可见. Example: 1
     * @bodyParam file_ids array 文件ID数组，最多20个. Example: [1,2,3]
     * @bodyParam location object 地理位置信息. Example: {"latitude":39.9042,"longitude":116.4074,"address":"北京市朝阳区"}
     * @bodyParam mentions array @用户ID数组，最多20个. Example: [1,2,3]
     * @bodyParam topics array 话题ID数组，最多10个. Example: [10,20]
     *
     * @response 201 {
     *   "code": 201,
     *   "message": "文章发布成功",
     *   "data": {
     *     "id": "1",
     *     "type": "article",
     *     "title": "我的第一篇文章",
     *     "contentHtml": "这是文章的内容...",
     *     "status": 1,
     *     "created_at": "2025-12-23T08:53:48.000000Z"
     *   }
     * }
     * @response 422 {
     *   "code": 422,
     *   "message": "验证失败",
     *   "errors": {
     *     "title": ["文章标题不能为空"]
     *   }
     * }
     */
    public function store(StoreArticleRequest $request): JsonResponse
    {
        // 检查创建权限
        $this->authorize('create', Post::class);

        $data = $request->validated();
        $data['type'] = Post::TYPE_ARTICLE; // 设置类型为文章
        $data['user_id'] = $this->userService->getCurrentUserId();

        $article = $this->articleService->create($data, $request);

        return response()->json([
            'code' => 201,
            'message' => '文章发布成功',
            'data' => new ArticleResource($article),
        ], 201);
    }

    /**
     * 更新文章
     *
     * @authenticated
     *
     * @bodyParam title string 文章标题，最多255字符. Example: 更新后的标题
     * @bodyParam content string 文章内容，最大50000字符. Example: 更新后的内容...
     * @bodyParam visibility int 可见性：1=公开,2=好友可见,3=私密,4=粉丝可见. Example: 1
     * @bodyParam file_ids array 文件ID数组，最多20个. Example: [1,2,3]
     * @bodyParam location object 地理位置信息. Example: {"latitude":39.9042,"longitude":116.4074,"address":"北京市朝阳区"}
     * @bodyParam mentions array @用户ID数组，最多20个. Example: [1,2,3]
     * @bodyParam topics array 话题ID数组，最多10个. Example: [10,20]
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "文章更新成功",
     *   "data": {
     *     "id": "1",
     *     "title": "更新后的标题",
     *     "contentHtml": "更新后的内容..."
     *   }
     * }
     */
    public function update(StoreArticleRequest $request, Post $post): JsonResponse
    {
        // 验证是否为文章类型
        if ($post->type !== Post::TYPE_ARTICLE) {
            return response()->json([
                'code' => 404,
                'message' => '文章不存在',
                'data' => null,
            ], 404);
        }

        // 检查更新权限
        $this->authorize('update', $post);

        $data = $request->validated();
        $article = $this->articleService->update($post->id, $data);

        return response()->json([
            'code' => 200,
            'message' => '文章更新成功',
            'data' => new ArticleResource($article),
        ], 200);
    }

    /**
     * 删除文章
     *
     * @authenticated
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "文章删除成功"
     * }
     */
    public function destroy(Post $post): JsonResponse
    {
        // 验证是否为文章类型
        if ($post->type !== Post::TYPE_ARTICLE) {
            return response()->json([
                'code' => 404,
                'message' => '文章不存在',
                'data' => null,
            ], 404);
        }

        // 检查删除权限
        $this->authorize('delete', $post);

        $this->articleService->delete($post->id);

        return response()->json([
            'code' => 200,
            'message' => '文章删除成功',
            'data' => null,
        ], 200);
    }
}