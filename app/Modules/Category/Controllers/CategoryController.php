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

namespace App\Modules\Category\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Category\Models\Category;
use App\Modules\Category\Requests\CreateCategoryRequest;
use App\Modules\Category\Requests\GetCategoriesRequest;
use App\Modules\Category\Requests\UpdateCategoryRequest;
use App\Modules\Category\Resources\CategoryResource;
use App\Modules\Category\Services\CategoryService;
use Illuminate\Http\JsonResponse;

/**
 * @group 分类模块
 *
 * @name 分类模块
 */
class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * 获取分类列表
     *
     * @queryParam search string 搜索关键词（分类名称或描述）
     * @queryParam is_active boolean 是否激活（0=未激活，1=激活）
     * @queryParam is_system boolean 是否系统分类（0=用户分类，1=系统分类）
     * @queryParam parent_id integer 父分类ID，为null时获取根分类
     * @queryParam sort_by string 排序字段（name, created_at, content_count, sort_order）
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
     *         "name": "技术",
     *         "slug": "tech",
     *         "description": "技术相关分类",
     *         "icon": "fas fa-code",
     *         "color": "#3498db",
     *         "parentId": null,
     *         "sortOrder": 0,
     *         "isActive": true,
     *         "isSystem": false,
     *         "depth": 0,
     *         "path": "技术",
     *         "url": "/categories/tech",
     *         "createdAt": "2024-01-01T00:00:00.000000Z"
     *       }
     *     ],
     *     "total": 1,
     *     "per_page": 20
     *   }
     * }
     */
    public function index(GetCategoriesRequest $request): JsonResponse
    {
        $params = $request->validated();
        $categories = $this->categoryService->getCategories($params);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => CategoryResource::collection($categories->items()),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
                'prev_page_url' => $categories->previousPageUrl(),
                'next_page_url' => $categories->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * 创建分类
     *
     * @authenticated
     *
     * @bodyParam name string required 分类名称，最多50字符
     * @bodyParam slug string 分类别名，用于URL，不填则自动生成
     * @bodyParam description string 分类描述，最多255字符
     * @bodyParam icon string 分类图标
     * @bodyParam color string 分类颜色，默认#3498db
     * @bodyParam parent_id integer 父分类ID
     * @bodyParam sort_order integer 排序权重，默认0
     * @bodyParam is_active boolean 是否激活，默认true
     * @bodyParam is_system boolean 是否系统分类，默认false
     * @bodyParam metadata object 扩展元数据
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "分类创建成功",
     *   "data": {
     *     "id": "1",
     *     "name": "技术",
     *     "slug": "tech",
     *     "description": "技术相关分类",
     *     "icon": "fas fa-code",
     *     "color": "#3498db",
     *     "parentId": null,
     *     "sortOrder": 0,
     *     "isActive": true,
     *     "isSystem": false,
     *     "createdAt": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     */
    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $category = $this->categoryService->create($data, $request->user());

        return response()->json([
            'code' => 200,
            'message' => '分类创建成功',
            'data' => new CategoryResource($category),
        ], 200);
    }

    /**
     * 获取分类详情
     *
     * @urlParam category string required 分类ID或slug
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "id": "1",
     *     "name": "技术",
     *     "slug": "tech",
     *     "description": "技术相关分类",
     *     "icon": "fas fa-code",
     *     "color": "#3498db",
     *     "parentId": null,
     *     "sortOrder": 0,
     *     "isActive": true,
     *     "isSystem": false,
     *     "depth": 0,
     *     "path": "技术",
     *     "url": "/categories/tech",
     *     "createdAt": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     */
    public function show(string $category): JsonResponse
    {
        $category = $this->categoryService->find($category, ['parent', 'children']);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => new CategoryResource($category),
        ], 200);
    }

    /**
     * 更新分类
     *
     * @authenticated
     *
     * @urlParam category integer required 分类ID
     * @bodyParam name string 分类名称，最多50字符
     * @bodyParam slug string 分类别名，用于URL
     * @bodyParam description string 分类描述，最多255字符
     * @bodyParam icon string 分类图标
     * @bodyParam color string 分类颜色
     * @bodyParam parent_id integer 父分类ID
     * @bodyParam sort_order integer 排序权重
     * @bodyParam is_active boolean 是否激活
     * @bodyParam is_system boolean 是否系统分类（管理员权限）
     * @bodyParam metadata object 扩展元数据
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "分类更新成功",
     *   "data": {
     *     "id": "1",
     *     "name": "技术",
     *     "slug": "tech",
     *     "description": "技术相关分类",
     *     "updatedAt": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     */
    public function update(UpdateCategoryRequest $request, int $categoryId): JsonResponse
    {
        $data = $request->validated();
        $category = $this->categoryService->update($categoryId, $data);

        return response()->json([
            'code' => 200,
            'message' => '分类更新成功',
            'data' => new CategoryResource($category),
        ], 200);
    }

    /**
     * 删除分类
     *
     * @authenticated
     *
     * @urlParam category integer required 分类ID
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "分类删除成功",
     *   "data": null
     * }
     */
    public function destroy(int $categoryId): JsonResponse
    {
        $this->categoryService->delete($categoryId);

        return response()->json([
            'code' => 200,
            'message' => '分类删除成功',
            'data' => null,
        ], 200);
    }

    /**
     * 获取分类树结构
     *
     * @queryParam only_active boolean 是否只获取激活的分类，默认true
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": [
     *     {
     *       "id": "1",
     *       "name": "技术",
     *       "slug": "tech",
     *       "parentId": null,
     *       "children": [
     *         {
     *           "id": "2",
     *           "name": "PHP",
     *           "slug": "php",
     *           "parentId": "1",
     *           "children": []
     *         }
     *       ]
     *     }
     *   ]
     * }
     */
    public function tree(): JsonResponse
    {
        $onlyActive = request('only_active', true);
        $categories = $this->categoryService->getCategoryTree($onlyActive);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => CategoryResource::collection($categories),
        ], 200);
    }

    /**
     * 获取根分类
     *
     * @queryParam only_active boolean 是否只获取激活的分类，默认true
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": [
     *     {
     *       "id": "1",
     *       "name": "技术",
     *       "slug": "tech",
     *       "parentId": null
     *     }
     *   ]
     * }
     */
    public function root(): JsonResponse
    {
        $onlyActive = request('only_active', true);
        $categories = $this->categoryService->getRootCategories($onlyActive);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => CategoryResource::collection($categories),
        ], 200);
    }

    /**
     * 获取子分类
     *
     * @urlParam category integer required 父分类ID
     * @queryParam only_active boolean 是否只获取激活的分类，默认true
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": [
     *     {
     *       "id": "2",
     *       "name": "PHP",
     *       "slug": "php",
     *       "parentId": "1"
     *     }
     *   ]
     * }
     */
    public function children(int $categoryId): JsonResponse
    {
        $onlyActive = request('only_active', true);
        $categories = $this->categoryService->getChildCategories($categoryId, $onlyActive);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => CategoryResource::collection($categories),
        ], 200);
    }

    /**
     * 移动分类
     *
     * @authenticated
     *
     * @urlParam category integer required 分类ID
     * @bodyParam parent_id integer 新父分类ID，为null时移到根分类
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "分类移动成功",
     *   "data": {
     *     "id": "1",
     *     "name": "技术",
     *     "parentId": "2"
     *   }
     * }
     */
    public function move(\Illuminate\Http\Request $request, int $categoryId): JsonResponse
    {
        $request->validate([
            'parent_id' => 'nullable|integer|exists:categories,id',
        ]);

        $category = $this->categoryService->moveCategory($categoryId, $request->parent_id);

        return response()->json([
            'code' => 200,
            'message' => '分类移动成功',
            'data' => new CategoryResource($category),
        ], 200);
    }

    /**
     * 批量更新排序
     *
     * @authenticated
     *
     * @bodyParam sort_data array required 排序数据数组 [['id' => 1, 'sort_order' => 10], ...]
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "排序更新成功",
     *   "data": null
     * }
     */
    public function batchUpdateSortOrder(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'sort_data' => 'required|array',
            'sort_data.*.id' => 'required|integer|exists:categories,id',
            'sort_data.*.sort_order' => 'required|integer|min:0',
        ]);

        $this->categoryService->batchUpdateSortOrder($request->sort_data);

        return response()->json([
            'code' => 200,
            'message' => '排序更新成功',
            'data' => null,
        ], 200);
    }

    /**
     * 批量操作分类（给内容设置/添加/移除分类）
     *
     * @authenticated
     *
     * @bodyParam action string required 操作类型（set, add, remove）
     * @bodyParam model_type string required 模型类型（post, article, topic等）
     * @bodyParam model_id integer required 模型ID
     * @bodyParam category_ids array required 分类ID数组
     *
     * @response 200 {
     *   "code": 200,
     *   "message": "操作成功",
     *   "data": {
     *     "currentCategories": [
     *       {
     *         "id": "1",
     *         "name": "技术",
     *         "slug": "tech"
     *       }
     *     ]
     *   }
     * }
     */
    public function batchOperation(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:set,add,remove',
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
            'category_ids' => 'required|array',
            'category_ids.*' => 'integer',
        ]);

        $model = $this->categoryService->getModelByType(
            $request->model_type,
            $request->model_id
        );

        $user = $request->user();

        switch ($request->action) {
            case 'set':
                $model->setCategories($request->category_ids, $user);
                $message = '分类设置成功';
                break;
            case 'add':
                $model->addCategories($request->category_ids, $user);
                $message = '分类添加成功';
                break;
            case 'remove':
                $model->removeCategories($request->category_ids);
                $message = '分类移除成功';
                break;
        }

        return response()->json([
            'code' => 200,
            'message' => $message,
            'data' => [
                'currentCategories' => $this->categoryService->getCategorizableCategories($model),
            ],
        ], 200);
    }
}