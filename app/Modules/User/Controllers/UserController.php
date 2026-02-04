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
use App\Modules\User\Models\User;
use App\Modules\User\Requests\UpdateAvatarRequest;
use App\Modules\User\Requests\UpdateProfileRequest;
use App\Modules\User\Resources\RecommendUserResource;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Services\UserFollowService;
use App\Modules\User\Services\UserMentionService;
use App\Modules\User\Services\UserService;
use App\Modules\Post\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

/**
 * @group 用户管理
 *
 * @name 用户管理
 */
class UserController extends Controller
{
    protected $service;

    protected $followService;

    protected $mentionService;

    protected $postService;

    public function __construct(UserService $service, UserFollowService $followService, UserMentionService $mentionService, PostService $postService)
    {
        $this->service = $service;
        $this->followService = $followService;
        $this->mentionService = $mentionService;
        $this->postService = $postService;
        $this->followService = $followService;
        $this->mentionService = $mentionService;
    }

    /**
     * 获取当前用户信息（兼容接口）.
     *
     * @deprecated 建议使用 GET /users/me 或 GET /users/{id}，后端会自动处理
     * @authenticated
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "id": 1,
     *     "username": "user1",
     *     "nickname": "用户1",
     *     "avatar": "avatars/xxx.jpg",
     *     "email": "user1@example.com",
     *     "bio": "个人简介",
     *     "status": 1,
     *     "created_at": "2024-01-01 00:00:00"
     *   }
     * }
     */
    public function me(): JsonResponse
    {
        // 兼容旧接口，内部调用 show 方法
        return $this->show('me');
    }

    /**
     * 获取个人资料.
     *
     * @authenticated
     *
     * @response {
     *   "code": 200,
     *   "message": "获取个人资料成功",
     *   "data": {
     *     "email": "user@example.com",
     *     "phone": "13800138000",
     *     "birthday": "1990-01-01",
     *     "gender": "男",
     *     "bio": "个人简介",
     *     "nickname": "昵称",
     *     "avatarUrl": "https://api2.yfsns.cn/storage/avatars/xxx.jpg"
     *   }
     * }
     */
    public function profile(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->error('未登录', 401);
        }

        $genderMap = [1 => '男', 2 => '女', 0 => '保密'];

        $data = [
            'email' => $user->email,
            'phone' => $user->phone,
            'birthday' => $user->birthday,
            'gender' => $genderMap[(int) $user->gender] ?? '保密',
            'bio' => $user->bio,
            'nickname' => $user->nickname,
            'avatarUrl' => $user->avatar_url,
        ];

        return response()->success($data, '获取个人资料成功');
    }

    /**
     * 获取用户详情（个人主页/个人中心）.
     *
     * 统一接口：支持传入用户名或"me"
     * - 如果传入"me"或当前用户名，返回完整信息（个人中心）
     * - 如果传入其他用户名，返回公开信息（个人主页）
     *
     * @urlParam username string required 用户名或"me"
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "id": "1",
     *     "username": "user1",
     *     "nickname": "用户1",
     *     "avatarUrl": "https://api2.yfsns.cn/storage/avatars/xxx.jpg",
     *     "gender": "男",
     *     "bio": "个人简介",
     *     "status": 1,
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "isFollowing": false,
     *     "isFollowed": false,
     *     "isMutualFollow": false,
     *     "followers": 100,
     *     "following": 50,
     *     "posts": 25,
     *     "collects": 15
     *   }
     * }
     */
    public function show(string $username): JsonResponse
    {
        $currentUser = auth()->user();

        // 处理 "me" 或当前用户名的情况
        if ($username === 'me' || ($currentUser && $currentUser->username === $username)) {
            // 个人中心：返回当前登录用户的完整信息
            if (!$currentUser) {
                return response()->error('未登录', 401);
            }
            $user = User::withCount(['collects'])->find($currentUser->id)->load('role');
            $isOwnProfile = true;
        } else {
            // 个人主页：返回其他用户的公开信息
            $user = User::withCount(['collects'])->where('username', $username)->firstOrFail()->load('role');
            $isOwnProfile = false;
        }

        // 获取用户基本信息
        $userData = (new UserResource($user))->toArray(request());

        // 添加关注状态（驼峰命名以匹配前端）
        // 如果是自己的个人中心，不需要关注状态
        if ($isOwnProfile) {
            $userData['isFollowing'] = false;
            $userData['isFollowed'] = false;
            $userData['isMutualFollow'] = false;
        } elseif ($currentUser) {
            // 查看其他用户时，显示关注状态
            $isFollowing = $this->followService->isFollowing($currentUser, $user);
            $isFollowed = $this->followService->isFollowing($user, $currentUser);
            $userData['isFollowing'] = $isFollowing;
            $userData['isFollowed'] = $isFollowed;
            $userData['isMutualFollow'] = $isFollowing && $isFollowed;
        } else {
            // 未登录用户，关注状态为false
            $userData['isFollowing'] = false;
            $userData['isFollowed'] = false;
            $userData['isMutualFollow'] = false;
        }

        // 添加统计数据（驼峰命名以匹配前端）
        $followStats = $this->followService->getUserFollowStats($user);
        $userData['followers'] = $followStats['followers'];
        $userData['following'] = $followStats['following'];
        $userData['posts'] = $user->posts()->count();

        return response()->success($userData, '获取用户信息成功');
    }

    /**
     * 更新个人资料.
     *
     * @authenticated
     *
     * @bodyParam nickname string 昵称
     * @bodyParam avatar string 头像
     * @bodyParam gender string 性别（男/女/保密）
     * @bodyParam birthday string 生日（YYYY-MM-DD格式）
     * @bodyParam bio string 个人简介
     *
     * @response {
     *   "code": 200,
     *   "message": "更新成功",
     *   "data": {
     *     "id": 1,
     *     "nickname": "新昵称",
     *     "avatar": "avatars/xxx.jpg",
     *     "bio": "新简介"
     *   }
     * }
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $data = $request->validated();

        // gender 字段字符串转数字
        if (isset($data['gender'])) {
            $map = [
                '男' => 1,
                '女' => 2,
                '保密' => 0,
            ];
            $data['gender'] = $map[$data['gender']] ?? 0;
        }

        $user = $this->service->updateProfile($data);

        return response()->success(new UserResource($user), '更新用户信息成功');
    }

    /**
     * 更新用户头像.
     *
     * @authenticated
     *
     * @bodyParam avatar file required 头像文件
     *
     * @response {
     *   "code": 200,
     *   "message": "更新成功",
     *   "data": {
     *     "avatar": "avatars/xxx.jpg"
     *   }
     * }
     */
    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->service->updateAvatar($request->file('avatar'));

        return response()->json([
            'avatar' => $result['path'],           // 原始路径
            'avatarUrl' => $result['avatar_url'],   // 驼峰格式
        ], 200);
    }

    /**
     * 注销账户.
     *
     * @authenticated
     *
     * @response {
     *   "code": 200,
     *   "message": "账户注销成功"
     * }
     */
    public function cancel(): JsonResponse
    {
        $this->service->cancelAccount();

        return response()->json([
            'message' => '账户注销成功',
        ], 200);
    }

    /**
     * 用户搜索（@弹窗）
     * 优化：返回完整的User对象，包含统计字段.
     *
     * @queryParam keyword string 搜索关键词
     *
     * @response 200 [
     *   {
     *     "id": "1",
     *     "username": "user1",
     *     "nickname": "张三",
     *     "avatar": "xxx",
     *     "avatarUrl": "http://...",
     *     "bio": "个人简介",
     *     "followers": 100,
     *     "following": 50,
     *     "posts": 30
     *   }
     * ]
     */
    public function search(Request $request)
    {
        $keyword = $request->input('keyword', '');
        $users = $this->service->searchUsers($keyword, 10);

        // 使用UserResource返回完整数据，包含统计字段（withCount已自动支持）
        return UserResource::collection($users);
    }

    /**
     * 获取推荐用户列表.
     *
     * 获取系统推荐的用户列表，用于首页展示等场景
     * 基于当前用户的关注情况进行个性化推荐
     *
     * @authenticated
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取推荐用户成功",
     *   "data": [
     *     {
     *       "id": "1",
     *       "username": "user1",
     *       "nickname": "用户1",
     *       "avatarUrl": "avatars/xxx.jpg",
     *       "gender": "男",
     *       "bio": "个人简介",
     *       "isFollowing": false,
     *       "isMutualFollow": false,
     *       "followers": 100
     *     }
     *   ]
     * }
     */
    public function recommend(): JsonResponse
    {
        $currentUser = auth('api')->user();

        $users = $this->service->getRecommendUsers(5);

        // 使用简化的RecommendUserResource并添加关注状态
        $data = $users->map(function ($user) use ($currentUser) {
            // 检查关注状态
            $isFollowing = $this->followService->isFollowing($currentUser, $user);
            $isFollowed = $this->followService->isFollowing($user, $currentUser);

            // 创建Resource实例并动态添加关注信息
            $resource = new RecommendUserResource($user);
            $userData = $resource->toArray(request());

            $userData['isFollowing'] = $isFollowing;
            $userData['isMutualFollow'] = $isFollowing && $isFollowed;
           $userData['followers'] = $this->followService->getFollowersCount($user);

            return $userData;
        });

        return response()->success($data, '获取推荐用户成功');
    }

    /**
     * 获取用户被@的动态列表.
     *
     * @authenticated
     *
     * @queryParam per_page int 每页数量，默认20
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "list": [
     *       {
     *         "id": "1",
     *         "content": "动态内容...",
     *         "author": {
     *           "id": "2",
     *           "username": "user2",
     *           "nickname": "用户2",
     *           "avatar": "avatars/xxx.jpg"
     *         },
     *         "mentioned_as": "张三",
     *         "created_at": "2024-01-01T00:00:00.000000Z"
     *       }
     *     ],
     *     "total": 100,
     *     "current_page": 1,
     *     "per_page": 20
     *   }
     * }
     */
    public function getMentionedPosts(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->input('per_page', 20)), 100);
        $currentUser = auth()->user();

        $posts = $this->mentionService->getUserMentionedPosts($currentUser->id, $perPage);

        // 转换为Resource
        $data = $posts->getCollection()->map(function ($post) {
            return [
                'id' => (string) $post->id,
                'content' => $post->content,
                'author' => [
                    'id' => (string) $post->user->id,
                    'username' => $post->user->username,
                    'nickname' => $post->user->nickname,
                    'avatar' => $post->user->avatar,
                ],
                'mentioned_as' => $post->mentioned_as ?? '用户',
                'created_at' => $post->created_at->toISOString(),
            ];
        });

        // 返回简洁的分页格式
        return response()->json([
            'data' => $data,
            'current_page' => $posts->currentPage(),
            'last_page' => $posts->lastPage(),
            'per_page' => $posts->perPage(),
            'total' => $posts->total(),
            'from' => $posts->firstItem(),
            'to' => $posts->lastItem(),
            'prev_page_url' => $posts->previousPageUrl(),
            'next_page_url' => $posts->nextPageUrl(),
        ], 200);
    }

    /**
     * 获取用户@统计信息.
     *
     * @authenticated
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "total_mentions": 100,
     *     "recent_mentions": 15
     *   }
     * }
     */
    public function getMentionStats(): JsonResponse
    {
        $currentUser = auth()->user();
        $stats = $this->mentionService->getUserMentionStats($currentUser->id);

        return response()->success($stats, '获取@统计成功');
    }

    /**
     * 获取用户的转发列表
     *
     * 获取指定用户的所有转发记录
     */
    public function getUserReposts(Request $request, int $userId): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 10), 50);

        $reposts = $this->postService->getRepostsByUser(
            $userId,
            $request->get('page', 1),
            $perPage
        );

        return response()->json([
            'data' => \App\Modules\Post\Resources\PostResource::collection($reposts),
            'current_page' => $reposts->currentPage(),
            'last_page' => $reposts->lastPage(),
            'per_page' => $reposts->perPage(),
            'total' => $reposts->total(),
        ], 200);
    }
}
