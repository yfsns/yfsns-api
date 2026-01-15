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
use App\Modules\User\Requests\BatchCheckFollowStatusRequest;
use App\Modules\User\Requests\GetFollowersRequest;
use App\Modules\User\Requests\GetFollowingRequest;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Services\UserFollowService;
use Illuminate\Http\JsonResponse;

/**
 * @group 用户管理
 */
class FollowController extends Controller
{

    protected $service;

    public function __construct(UserFollowService $service)
    {
        $this->service = $service;
    }

    /**
     * 关注用户.
     *
     * @authenticated
     */
    public function follow(User $user): JsonResponse
    {
        if (auth()->id() === $user->id) {
            return response()->error('不能关注自己', 400);
        }

        $this->service->follow(auth()->user(), $user);

        return response()->success(null, '关注成功');
    }

    /**
     * 取消关注.
     *
     * @authenticated
     */
    public function unfollow(User $user): JsonResponse
    {
        $this->service->unfollow(auth()->user(), $user);

        return response()->success(null, '已取消关注');
    }

    /**
     * 获取关注列表.
     *
     * @authenticated
     *
     * @queryParam user_id int 用户ID，不传则查看当前用户的关注列表
     * @queryParam per_page int 每页数量，默认20
     *
     * @response {
     *   "code": 200,
     *   "message": "获取关注列表成功",
     *   "data": {
     *     "list": [
     *       {
     *         "id": "1",
     *         "username": "user1",
     *         "nickname": "用户1",
     *         "avatar": "avatars/xxx.jpg",
     *         "gender": "男",
     *         "bio": "个人简介",
     *         "isMutualFollow": true,
     *         "followedAt": "2024-01-01T00:00:00.000000Z"
     *       }
     *     ],
     *     "total": 100,
     *     "current_page": 1,
     *     "per_page": 20
     *   }
     * }
     */
    public function following(GetFollowingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $perPage = min((int) ($data['per_page'] ?? 20), 100);
        $cursor = $data['cursor'] ?? null;
        $userId = $data['user_id'] ?? null;

        // 如果传了 user_id，查看指定用户的关注列表，否则查看当前用户的
        $user = $userId ? User::findOrFail($userId) : auth()->user();
        $following = $this->service->following($user, $perPage, $cursor);

        // 转换为 Resource 并添加关注状态（驼峰命名以匹配前端）
        $items = $following->getCollection()->map(function ($userFollow) {
            // $userFollow 是 UserFollow 对象，需要通过 following 关系获取实际的 User 对象
            $followedUser = $userFollow->following;
            
            $userData = (new UserResource($followedUser))->toArray(request());
            $isFollowing = $this->service->isFollowing(auth()->user(), $followedUser);
            $isFollowed = $this->service->isFollowing($followedUser, auth()->user());

            $userData['isFollowing'] = $isFollowing;
            $userData['isFollowed'] = $isFollowed;
            $userData['isMutualFollow'] = $isFollowing && $isFollowed;
            $userData['followedAt'] = $userFollow->created_at->toIso8601String();

            return $userData;
        })->toArray();

        // 返回驼峰格式的游标分页数据
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => $items,
                'perPage' => $following->perPage(),
                'nextCursor' => $following->nextCursor()?->encode(),
                'nextPageUrl' => $following->nextPageUrl(),
                'prevCursor' => $following->previousCursor()?->encode(),
                'prevPageUrl' => $following->previousPageUrl(),
            ]
        ], 200);
    }

    /**
     * 获取粉丝列表.
     *
     * @authenticated
     *
     * @queryParam user_id int 用户ID，不传则查看当前用户的粉丝列表
     * @queryParam per_page int 每页数量，默认20
     *
     * @response {
     *   "code": 200,
     *   "message": "获取粉丝列表成功",
     *   "data": {
     *     "list": [
     *       {
     *         "id": "1",
     *         "username": "user1",
     *         "nickname": "用户1",
     *         "avatar": "avatars/xxx.jpg",
     *         "gender": "男",
     *         "bio": "个人简介",
     *         "isMutualFollow": true,
     *         "followedAt": "2024-01-01T00:00:00.000000Z"
     *       }
     *     ],
     *     "total": 100,
     *     "current_page": 1,
     *     "per_page": 20
     *   }
     * }
     */
    public function followers(GetFollowersRequest $request): JsonResponse
    {
        $data = $request->validated();
        $perPage = min((int) ($data['per_page'] ?? 20), 100);
        $cursor = $data['cursor'] ?? null;
        $userId = $data['user_id'] ?? null;

        // 如果传了 user_id，查看指定用户的粉丝列表，否则查看当前用户的
        $user = $userId ? User::findOrFail($userId) : auth()->user();
        $followers = $this->service->followers($user, $perPage, $cursor);

        // 转换为 Resource 并添加关注状态（驼峰命名以匹配前端）
        $items = $followers->getCollection()->map(function ($userFollow) {
            // $userFollow 是 UserFollow 对象，需要通过 follower 关系获取实际的 User 对象
            $followerUser = $userFollow->follower;
            
            $userData = (new UserResource($followerUser))->toArray(request());
            $isFollowing = $this->service->isFollowing(auth()->user(), $followerUser);
            $isFollowed = $this->service->isFollowing($followerUser, auth()->user());

            $userData['isFollowing'] = $isFollowing;
            $userData['isFollowed'] = $isFollowed;
            $userData['isMutualFollow'] = $isFollowing && $isFollowed;
            $userData['followedAt'] = $userFollow->created_at->toIso8601String();

            return $userData;
        })->toArray();

        // 返回驼峰格式的游标分页数据
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => $items,
                'perPage' => $followers->perPage(),
                'nextCursor' => $followers->nextCursor()?->encode(),
                'nextPageUrl' => $followers->nextPageUrl(),
                'prevCursor' => $followers->previousCursor()?->encode(),
                'prevPageUrl' => $followers->previousPageUrl(),
            ]
        ], 200);
    }

    /**
     * 检查是否已关注指定用户.
     *
     * @authenticated
     *
     * @urlParam user int required 要检查的用户ID
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "isFollowing": true,
     *     "userId": 2
     *   }
     * }
     */
    public function checkFollowStatus(User $user): JsonResponse
    {
        $isFollowing = $this->service->isFollowing(auth()->user(), $user);

        return response()->json([
            'isFollowing' => $isFollowing,
            'userId' => $user->id,
        ], 200);
    }

    /**
     * 批量检查关注状态
     *
     * @authenticated
     *
     * @bodyParam user_ids array required 用户ID列表
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "followStatus": {
     *       "2": true,
     *       "3": false,
     *       "4": true
     *     }
     *   }
     * }
     */
    public function batchCheckFollowStatus(BatchCheckFollowStatusRequest $request): JsonResponse
    {
        $data = $request->validated();

        $currentUser = auth()->user();
        $followStatus = [];

        foreach ($data['user_ids'] as $userId) {
            $user = User::find($userId);
            $followStatus[(string) $userId] = $this->service->isFollowing($currentUser, $user);
        }

        return response()->json([
            'followStatus' => $followStatus,
        ], 200);
    }
}
