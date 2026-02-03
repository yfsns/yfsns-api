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

namespace App\Modules\Tag\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tag\Models\Tag;
use App\Modules\Tag\Requests\CreateTagRequest;
use App\Modules\Tag\Requests\GetTagsRequest;
use App\Modules\Tag\Requests\UpdateTagRequest;
use App\Modules\Tag\Resources\TagResource;
use App\Modules\Tag\Services\TagService;
use Illuminate\Http\JsonResponse;

/**
 * @group 标签模块
 *
 * @name 标签模块
 */
class TagController extends Controller
{
    protected TagService $tagService;

    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    /**
     * 获取标签列表
     *
     * @queryParam search string 搜索关键词（标签名称或描述）
     * @queryParam is_system boolean 是否系统标签（0=用户标签，1=系统标签）
     * @queryParam sort_by string 排序字段（usage_count, name, created_at, sort_order）
     * @queryParam sort_direction string 排序方向（asc, desc）
     * @queryParam per_page integer 每页数量，默认20，最大100
     * @queryParam page integer 页码，默认1
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": "1",
     *         "name": "PHP",
     *         "slug": "php",
     *         "description": "PHP编程语言",
     *         "color": "#777bb3",
     *         "usageCount": 25,
     *         "sortOrder": 0,
     *         "isSystem": false,
     *         "url": "/tags/php",
     *         "displayName": "PHP",
     *         "createdAt": "2024-01-01T00:00:00.000000Z"
     *       }
     *     ],
     *     "total": 1,
     *     "per_page": 20
     *   }
     * }
     */
    public function index(GetTagsRequest $request): JsonResponse
    {
        $params = $request->validated();
        $tags = $this->tagService->getTags($params);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => TagResource::collection($tags->items()),
                'current_page' => $tags->currentPage(),
                'last_page' => $tags->lastPage(),
                'per_page' => $tags->perPage(),
                'total' => $tags->total(),
                'from' => $tags->firstItem(),
                'to' => $tags->lastItem(),
                'prev_page_url' => $tags->previousPageUrl(),
                'next_page_url' => $tags->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * 创建标签
     *
     * @authenticated
     *
     * @bodyParam name string required 标签名称，最多50字符
     * @bodyParam slug string 标签别名，用于URL，不填则自动生成
     * @bodyParam description string 标签描述，最多255字符
     * @bodyParam color string 标签颜色，默认#3498db
     * @bodyParam sort_order integer 排序权重，默认0
     * @bodyParam is_system boolean 是否系统标签，默认false
     * @bodyParam metadata object 扩展元数据
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "标签创建成功",
     *   "data": {
     *     "id": "1",
     *     "name": "PHP",
     *     "slug": "php",
     *     "description": "PHP编程语言",
     *     "color": "#777bb3",
     *     "usageCount": 0,
     *     "sortOrder": 0,
     *     "isSystem": false,
     *     "createdAt": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     */
    public function store(CreateTagRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tag = $this->tagService->create($data, $request->user());

        return response()->json([
            'code' => 200,
            'message' => '标签创建成功',
            'data' => new TagResource($tag),
        ], 200);
    }

    /**
     * 获取标签详情
     *
     * @urlParam tag string required 标签ID或slug
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "id": "1",
     *     "name": "PHP",
     *     "slug": "php",
     *     "description": "PHP编程语言",
     *     "color": "#777bb3",
     *     "usageCount": 25,
     *     "sortOrder": 0,
     *     "isSystem": false,
     *     "url": "/tags/php",
     *     "displayName": "PHP",
     *     "createdAt": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     */
    public function show(string $tag): JsonResponse
    {
        $tag = $this->tagService->find($tag);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => new TagResource($tag),
        ], 200);
    }

    /**
     * 更新标签
     *
     * @authenticated
     *
     * @urlParam tag integer required 标签ID
     * @bodyParam name string 标签名称，最多50字符
     * @bodyParam slug string 标签别名，用于URL
     * @bodyParam description string 标签描述，最多255字符
     * @bodyParam color string 标签颜色
     * @bodyParam sort_order integer 排序权重
     * @bodyParam is_system boolean 是否系统标签（管理员权限）
     * @bodyParam metadata object 扩展元数据
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "标签更新成功",
     *   "data": {
     *     "id": "1",
     *     "name": "PHP",
     *     "slug": "php",
     *     "description": "PHP编程语言",
     *     "color": "#777bb3",
     *     "usageCount": 25,
     *     "sortOrder": 0,
     *     "isSystem": false,
     *     "updatedAt": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     */
    public function update(UpdateTagRequest $request, int $tagId): JsonResponse
    {
        $data = $request->validated();
        $tag = $this->tagService->update($tagId, $data);

        return response()->json([
            'code' => 200,
            'message' => '标签更新成功',
            'data' => new TagResource($tag),
        ], 200);
    }

    /**
     * 删除标签
     *
     * @authenticated
     *
     * @urlParam tag integer required 标签ID
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "标签删除成功",
     *   "data": null
     * }
     */
    public function destroy(int $tagId): JsonResponse
    {
        $this->tagService->delete($tagId);

        return response()->json([
            'code' => 200,
            'message' => '标签删除成功',
            'data' => null,
        ], 200);
    }

    /**
     * 获取热门标签
     *
     * @queryParam limit integer 数量限制，默认20
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": [
     *     {
     *       "id": "1",
     *       "name": "PHP",
     *       "slug": "php",
     *       "usageCount": 25,
     *       "color": "#777bb3"
     *     }
     *   ]
     * }
     */
    public function popular(): JsonResponse
    {
        $limit = min(request('limit', 20), 50);
        $tags = $this->tagService->getPopularTags($limit);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => TagResource::collection($tags),
        ], 200);
    }

    /**
     * 获取系统标签
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": [
     *     {
     *       "id": "1",
     *       "name": "推荐",
     *       "slug": "recommended",
     *       "isSystem": true,
     *       "color": "#e74c3c"
     *     }
     *   ]
     * }
     */
    public function system(): JsonResponse
    {
        $tags = $this->tagService->getSystemTags();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => TagResource::collection($tags),
        ], 200);
    }

    /**
     * 批量操作标签（给内容添加/移除标签）
     *
     * @authenticated
     *
     * @bodyParam action string required 操作类型（attach, detach, sync）
     * @bodyParam model_type string required 模型类型（post, article, topic等）
     * @bodyParam model_id integer required 模型ID
     * @bodyParam tag_ids array required 标签ID数组
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "操作成功",
     *   "data": {
     *     "attached": [1, 2],
     *     "detached": [],
     *     "currentTags": [
     *       {
     *         "id": "1",
     *         "name": "PHP",
     *         "slug": "php"
     *       }
     *     ]
     *   }
     * }
     */
    public function batchOperation(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:attach,detach,sync',
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'integer',
        ]);

        $model = $this->tagService->getModelByType(
            $request->model_type,
            $request->model_id
        );

        $user = $request->user();

        switch ($request->action) {
            case 'attach':
                $model->attachTags($request->tag_ids, $user);
                $message = '标签添加成功';
                break;
            case 'detach':
                $model->detachTags($request->tag_ids);
                $message = '标签移除成功';
                break;
            case 'sync':
                $model->syncTags($request->tag_ids, $user);
                $message = '标签同步成功';
                break;
        }

        return response()->json([
            'code' => 200,
            'message' => $message,
            'data' => [
                'currentTags' => $this->tagService->getTaggableTags($model),
            ],
        ], 200);
    }
}