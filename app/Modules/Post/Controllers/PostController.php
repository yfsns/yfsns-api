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
use App\Modules\Post\Requests\GetPostsRequest;
use App\Modules\Post\Requests\GetPostDetailRequest;
use App\Modules\Post\Requests\StorePostRequest;
use App\Modules\Post\Requests\UpdatePostRequest;
use App\Modules\Post\Resources\PostResource;
use App\Modules\Post\Services\PostService;
use App\Modules\User\Services\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use function in_array;
use function is_array;

/**
 * @group 动态模块
 *
 * @name 动态模块
 */
class PostController extends Controller
{
    protected $postService;
    protected $userService;

    public function __construct(PostService $postService, UserService $userService)
    {
        $this->postService = $postService;
        $this->userService = $userService;
    }

    /**
     * 创建动态
     *
     * @authenticated
     *
     * @bodyParam title string 标题，文章、问题、话题类型必填，最多255字符. Example: 我的第一篇文章
     * @bodyParam content string 与file_ids至少提供一项，最大2000字符. Example: 这是一篇很有意义的文章内容...
     * @bodyParam type string required 内容类型：post/article/question/thread/image/video. Example: article
     * @bodyParam visibility int required 可见性：1=公开,2=好友可见,3=私密,4=粉丝可见. Example: 1
     * @bodyParam file_ids array 文件ID数组，图片/视频类型必填，最多20个. Example: [1,2,3]
     * @bodyParam fileIds array 文件ID数组（驼峰格式）. Example: [1,2,3]
     * @bodyParam location object 地理位置信息. Example: {"latitude":39.9042,"longitude":116.4074,"address":"北京市朝阳区"}
     * @bodyParam mentions array @用户ID数组，最多20个. Example: [1,2,3]
     * @bodyParam topics array 话题ID数组，最多10个. Example: [10,20]
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "动态已发布",
     *   "data": {
     *     "id": 1,
     *     "title": "我的第一篇文章",
     *     "content": "这是一篇很有意义的文章内容...",
     *     "type": "article",
     *     "visibility": 1,
     *     "status": 1,
     *     "created_at": "2025-12-23T08:53:48.000000Z"
     *   }
     * }
     * @response 422 {
     *   "code": 422,
     *   "message": "验证失败",
     *   "errors": {
     *     "content": ["内容和文件至少要提供一项"]
     *   }
     * }
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        // 使用 Policy 检查权限，失败自动返回 403
        $this->authorize('create', Post::class);

        $data = $request->validated();

        // 数据预处理：确保类型正确
        $data['visibility'] = (int) ($data['visibility'] ?? 1);

        // 合并 file_ids 字段（支持下划线和驼峰格式）
        $data['file_ids'] = $data['file_ids'] ?? $data['fileIds'] ?? [];

        // 过滤无效文件ID（验证规则确保了基本验证，这里只需要清理）
        $data['file_ids'] = array_filter($data['file_ids'], 'is_numeric');

        $post = $this->postService->create($data, $this->userService->getCurrentUserId());

        // 加载创建动态所需的关联数据
        $post->load([
            'user' => function ($query) {
                $query->withEssentialFields('status');
            },
            'files:id,name,path,type,size,mime_type,storage,thumbnail,created_at,updated_at',
            'mentions:id,sender_id,receiver_id,username,nickname_at_time',
            'mentions.receiver' => function ($query) {
                $query->selectBasic();
            },
            'topics:id,name,description,cover,post_count,follower_count',
            // 加载转发关联的原动态（如果存在）
            'originalPost.user' => function ($query) {
                $query->withEssentialFields('status');
            },
            'originalPost.files:id,name,path,type,size,mime_type,storage,thumbnail,created_at,updated_at',
        ]);

        return response()->json([
            'code' => 200,
            'message' => '动态已发布',
            'data' => new PostResource($post),
        ], 200);
    }

    /**
     * 获取动态详情
     *
     * @queryParam type string required 动态类型，必须与动态的实际类型匹配. Example: post
     */
    public function getDetail(Post $post, GetPostDetailRequest $request): JsonResponse
    {
        // 验证是否为动态类型（post）
        if ($post->type !== Post::TYPE_POST) {
            return response()->json([
                'code' => 404,
                'message' => '动态不存在',
                'data' => null,
            ], 404);
        }

        // 使用 Policy 检查查看权限，失败自动返回 403
        $this->authorize('view', $post);

        // 加载动态详情所需的关联数据和用户交互状态
        $relations = [
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
        ];

        // 只有在存在转发ID时才加载原动态的关系
        if ($post->repost_id) {
            $relations = array_merge($relations, [
                'originalPost.user' => function ($query) {
                    $query->withEssentialFields('status');
                },
                'originalPost.files:id,name,path,type,size,mime_type,storage,thumbnail,created_at,updated_at',
            ]);
        }

        $post->load($relations);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => new PostResource($post),
        ], 200);
    }

    /**
     * 更新动态
     *
     * @authenticated
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        // 使用 Policy 检查权限，失败自动返回 403
        $this->authorize('update', $post);

        $data = $request->validated();

        // 过滤掉 file_ids 中的 null 值
        if (isset($data['file_ids']) && is_array($data['file_ids'])) {
            $data['file_ids'] = array_filter($data['file_ids'], fn ($value) => $value !== null);
        }

        $post = $this->postService->update($post->id, $data);

        return response()->json([
            'code' => 200,
            'message' => '动态已更新',
            'data' => new PostResource($post),
        ], 200);
    }

    /**
     * 删除动态
     *
     * @authenticated
     */
    public function destroy(Post $post): JsonResponse
    {
        // 使用 Policy 检查权限，失败自动返回 403
        $this->authorize('delete', $post);

        $this->postService->delete($post->id);

        return response()->json([
            'code' => 200,
            'message' => '动态已删除',
            'data' => ['message' => '动态已删除'],
        ], 200);
    }

    /**
     * 获取动态列表.
     *
     * 支持内容类型过滤（type参数）：
     * - post: 动态
     * - article: 文章
     * - question: 提问
     * - thread: 帖子
     * - image: 图片动态（只能包含图片）
     * - video: 视频动态（只能包含视频）
     *
     * 支持查询筛选（filter参数）：
     * - all: 按可见性规则的所有内容（游客只看公开；登录用户可看到自己的所有动态及对其可见的动态）
     * - media: 包含媒体的内容（仅当 type=post 时有效）
     * - liked: 用户点赞的内容
     * - user: 指定用户的内容
     * - my: 当前用户的内容
     * - following: 关注用户的内容
     */
    public function getPosts(GetPostsRequest $request): JsonResponse
    {
        // 获取验证后的数据（已经是下划线格式）
        $validated = $request->validated();

        $params = [
            'type' => $validated['type'],
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
                'message' => '获取用户帖子时必须提供userId参数',
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
     * 转发动态
     *
     * 转发一条动态，可以添加转发内容
     */
    public function repost(Request $request, int $postId): JsonResponse
    {
        // 手动验证转发请求
        $rules = [
            'content' => 'nullable|string|max:1000',
        ];

        $data = $request->validate($rules);
        $content = $data['content'] ?? null;

        $repost = $this->postService->repost($postId, $content);

        // 加载必要的关联数据
        $repost->load([
            'user:id,username,nickname,avatar',
            'originalPost' => function ($query) {
                $query->with([
                    'user:id,username,nickname,avatar'
                ]);
            },
        ]);

        return response()->json([
            'code' => 200,
            'message' => '转发成功',
            'data' => new PostResource($repost),
        ], 200);
    }

    /**
     * 取消转发
     *
     * 取消对一条动态的转发
     */
    public function unrepost(int $postId): JsonResponse
    {
        $this->postService->unrepost($postId);

        return response()->json([
            'code' => 200,
            'message' => '取消转发成功',
            'data' => null,
        ], 200);
    }

    /**
     * 获取动态的转发列表
     *
     * 获取一条动态的所有转发记录
     */
    public function getReposts(int $postId, Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 10), 50);

        $reposts = $this->postService->getRepostsByOriginalPost(
            $postId,
            $request->get('page', 1),
            $perPage
        );

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => PostResource::collection($reposts),
                'current_page' => $reposts->currentPage(),
                'last_page' => $reposts->lastPage(),
                'per_page' => $reposts->perPage(),
                'total' => $reposts->total(),
            ],
        ], 200);
    }

    /**
     * 根据内容类型获取对应的Resource
     */
}
