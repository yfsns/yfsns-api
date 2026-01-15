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

namespace App\Modules\Share\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Post\Models\Post;
use App\Modules\Share\Requests\GetSharesRequest;
use App\Modules\Share\Requests\GetShareUrlRequest;
use App\Modules\Share\Requests\ShareRequest;
use App\Modules\Share\Resources\ShareResource;
use App\Modules\Share\Services\ShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 分享模块
 *
 * @name 分享模块
 */
class ShareController extends Controller
{

    protected $shareService;

    public function __construct(ShareService $shareService)
    {
        $this->shareService = $shareService;
    }

    /**
     * 分享内容.
     *
     * @authenticated
     *
     * @bodyParam platform string required 分享平台,可选值:wechat、weibo、qq Example: wechat
     * @bodyParam type string 分享类型,可选值:default、timeline、friend Example: default
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "分享成功",
     *   "data": {
     *     "id": 1,
     *     "user_id": 1,
     *     "content": "分享内容",
     *     "type": "post",
     *     "target_id": 1,
     *     "created_at": "2024-03-20 10:00:00"
     *   }
     * }
     */
    public function share(ShareRequest $request): JsonResponse
    {
        $data = $request->validated();

        $post = Post::findOrFail($request->id);
        $result = $this->shareService->share(
            $post,
            $data['platform'],
            $data['type'],
            $request
        );

        return response()->json([
            'code' => 200,
            'message' => $result['message'],
            'data' => new ShareResource($result['share']),
        ], 200);
    }

    /**
     * 取消分享.
     *
     * @authenticated
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "取消成功"
     * }
     */
    public function unshare(Request $request): JsonResponse
    {
        $post = Post::findOrFail($request->id);
        $result = $this->shareService->unshare($post);

        if (! $result) {
            return response()->json([
                'code' => 400,
                'message' => '未找到分享记录',
                'data' => null,
            ], 400);
        }

        return response()->json([
            'code' => 200,
            'message' => '取消成功',
            'data' => null,
        ], 200);
    }

    /**
     * 获取分享列表.
     *
     * @authenticated
     *
     * @queryParam type string 分享类型,可选值:all、wechat、weibo、qq Example: all
     * @queryParam page integer 页码,默认1 Example: 1
     * @queryParam per_page integer 每页数量,默认10,最大100 Example: 10
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "list": [
     *       {
     *         "id": 1,
     *         "user_id": 1,
     *         "content": "分享内容",
     *         "type": "post",
     *         "target_id": 1,
     *         "created_at": "2024-03-20 10:00:00"
     *       }
     *     ],
     *     "total": 1,
     *     "page": 1,
     *     "per_page": 10
     *   }
     * }
     */
    public function list(GetSharesRequest $request): JsonResponse
    {
        $data = $request->validated();

        $shares = $this->shareService->getUserShares(
            $data['type'] ?? null,
            Post::class,
            $data['page'] ?? 1,
            $data['per_page'] ?? 10
        );

        // 返回Laravel原生分页格式
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => ShareResource::collection($shares->items()),
                'current_page' => $shares->currentPage(),
                'last_page' => $shares->lastPage(),
                'per_page' => $shares->perPage(),
                'total' => $shares->total(),
                'from' => $shares->firstItem(),
                'to' => $shares->lastItem(),
                'prev_page_url' => $shares->previousPageUrl(),
                'next_page_url' => $shares->nextPageUrl(),
            ]
        ]);
    }

    /**
     * 检查是否已分享.
     *
     * @authenticated
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "shared": true
     *   }
     * }
     */
    public function check(Request $request): JsonResponse
    {
        $post = Post::findOrFail($request->id);
        $shared = $this->shareService->check($post);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => ['shared' => $shared],
        ], 200);
    }

    /**
     * 获取分享数量.
     *
     * @authenticated
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "count": 10
     *   }
     * }
     */
    public function count(Request $request): JsonResponse
    {
        $post = Post::findOrFail($request->id);
        $count = $this->shareService->getShareCount($post);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => ['count' => $count],
        ], 200);
    }

    /**
     * 获取分享链接.
     *
     * @queryParam platform string required 分享平台,可选值:wechat、weibo、qq Example: wechat
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "url": "https://example.com/share/post/1?platform=wechat"
     *   }
     * }
     */
    public function url(GetShareUrlRequest $request): JsonResponse
    {
        $data = $request->validated();

        $post = Post::findOrFail($request->id);
        $url = $this->shareService->getShareUrl($post, $data['platform']);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => ['url' => $url],
        ], 200);
    }
}
