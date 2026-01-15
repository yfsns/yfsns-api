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

namespace App\Modules\SensitiveWord\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\SensitiveWord\Models\SensitiveWord;
use App\Modules\SensitiveWord\Models\SensitiveWordLog;
use App\Modules\SensitiveWord\Requests\Admin\BatchDestroySensitiveWordsRequest;
use App\Modules\SensitiveWord\Requests\Admin\BatchImportSensitiveWordsRequest;
use App\Modules\SensitiveWord\Requests\Admin\StoreSensitiveWordRequest;
use App\Modules\SensitiveWord\Requests\Admin\TestFilterRequest;
use App\Modules\SensitiveWord\Requests\Admin\UpdateSensitiveWordRequest;
use App\Modules\SensitiveWord\Resources\SensitiveWordLogResource;
use App\Modules\SensitiveWord\Resources\SensitiveWordResource;
use App\Modules\SensitiveWord\Services\SensitiveWordService;

use const ARRAY_FILTER_USE_KEY;

use function count;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use function in_array;

/**
 * @group admin-后台管理-敏感词管理
 *
 * @name 后台管理-敏感词管理
 *
 * @authenticated
 */
class SensitiveWordController extends Controller
{

    protected $service;

    public function __construct(SensitiveWordService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取敏感词列表.
     *
     * @authenticated
     *
     * @queryParam page int 页码
     * @queryParam per_page int 每页数量（支持驼峰格式 perPage）
     * @queryParam keyword string 关键词搜索
     * @queryParam category string 分类筛选
     * @queryParam level string 级别筛选
     * @queryParam status boolean 状态筛选
     */
    public function index(Request $request): JsonResponse
    {
        $query = SensitiveWord::query();

        // 关键词搜索
        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request): void {
                $q->where('word', 'like', '%' . $request->keyword . '%')
                    ->orWhere('description', 'like', '%' . $request->keyword . '%');
            });
        }

        // 分类筛选
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // 级别筛选
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        // 状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->boolean('status'));
        }

        // 按 id 降序排序（最新的在前）
        $query->orderBy('id', 'desc');

        // 使用统一的分页参数处理（支持驼峰格式 perPage）
        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        $paginationParams = [
            'per_page' => $perPage,
            'page' => $request->input('page'),
        ];
        $perPage = $paginationParams['per_page'];
        $page = $paginationParams['page'] ?? null;

        if ($page !== null) {
            $words = $query->paginate($perPage, ['*'], 'page', $page);
        } else {
            $words = $query->paginate($perPage);
        }

        // 返回Laravel原生分页格式
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => SensitiveWordResource::collection($words->items()),
                'current_page' => $words->currentPage(),
                'last_page' => $words->lastPage(),
                'per_page' => $words->perPage(),
                'total' => $words->total(),
                'from' => $words->firstItem(),
                'to' => $words->lastItem(),
                'prev_page_url' => $words->previousPageUrl(),
                'next_page_url' => $words->nextPageUrl(),
            ]
        ]);
    }

    /**
     * 获取敏感词统计
     *
     * @authenticated
     */
    public function stats(): JsonResponse
    {
        $stats = $this->service->getStats();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $stats,
        ], 200);
    }

    /**
     * 获取敏感词详情.
     *
     * @authenticated
     */
    public function show(int $id): JsonResponse
    {
        $word = SensitiveWord::findOrFail($id);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => new SensitiveWordResource($word),
        ], 200);
    }

    /**
     * 创建敏感词.
     *
     * @authenticated
     *
     * @bodyParam word string required 敏感词
     * @bodyParam category string 分类
     * @bodyParam level string 级别
     * @bodyParam action string 处理方式
     * @bodyParam replacement string 替换内容
     * @bodyParam is_regex boolean 是否正则表达式
     */
    public function store(StoreSensitiveWordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $word = SensitiveWord::create([
            'word' => $data['word'],
            'category' => $data['category'],
            'level' => $data['level'],
            'action' => $data['action'],
            'replacement' => $data['replacement'] ?? null,
            'is_regex' => $data['is_regex'] ?? false,
            'description' => $data['description'] ?? null,
            'status' => true,
            'created_by' => auth()->id(),
        ]);

        $this->service->clearCache();

        return response()->json([
            'code' => 200,
            'message' => '创建成功',
            'data' => new SensitiveWordResource($word),
        ], 200);
    }

    /**
     * 更新敏感词.
     *
     * @authenticated
     */
    public function update(UpdateSensitiveWordRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        $word = SensitiveWord::findOrFail($id);
        
        // 准备更新数据
        $updateData = array_filter($data, function ($key) {
                return in_array($key, ['word', 'category', 'level', 'action', 'replacement', 'description']);
        }, ARRAY_FILTER_USE_KEY);
        
        // 处理 is_regex（只有明确传递时才更新）
        if (isset($data['is_regex'])) {
            $updateData['is_regex'] = $data['is_regex'];
        }
        
        // 处理 status（只有明确传递时才更新，避免前端误传 false）
        if (array_key_exists('status', $data)) {
            $updateData['status'] = $data['status'];
        }
        
        $updateData['updated_by'] = auth()->id();
        
        $word->update($updateData);

        $this->service->clearCache();

        return response()->json([
            'code' => 200,
            'message' => '更新成功',
            'data' => new SensitiveWordResource($word),
        ], 200);
    }

    /**
     * 删除敏感词.
     *
     * @authenticated
     */
    public function destroy(int $id): JsonResponse
    {
        $word = SensitiveWord::findOrFail($id);
        $word->delete();

        $this->service->clearCache();

        return response()->json([
            'code' => 200,
            'message' => '删除成功',
            'data' => null,
        ], 200);
    }

    /**
     * 批量删除.
     *
     * @authenticated
     *
     * @bodyParam ids array required 敏感词ID数组
     */
    public function batchDestroy(BatchDestroySensitiveWordsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $count = SensitiveWord::whereIn('id', $data['ids'])->delete();

        $this->service->clearCache();

        return response()->json([
            'code' => 200,
            'message' => "成功删除 {$count} 条记录",
            'data' => ['count' => $count],
        ], 200);
    }

    /**
     * 批量导入.
     *
     * @authenticated
     *
     * @bodyParam words array required 敏感词数组
     * @bodyParam category string 分类
     * @bodyParam level string 级别
     * @bodyParam action string 处理方式
     */
    public function batchImport(BatchImportSensitiveWordsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->service->batchImport(
            $data['words'],
            $data['category'],
            $data['level'],
            $data['action']
        );

        return response()->json([
            'code' => 200,
            'message' => '导入完成',
            'data' => $result,
        ], 200);
    }

    /**
     * 导出敏感词.
     *
     * @authenticated
     *
     * @queryParam category string 分类筛选
     */
    public function export(Request $request)
    {
        $query = SensitiveWord::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $words = $query->pluck('word')->toArray();

        return response()->json([
            'code' => 200,
            'message' => '导出成功',
            'data' => [
                'words' => $words,
                'count' => count($words),
            ],
        ]);
    }

    /**
     * 获取配置选项.
     *
     * @authenticated
     */
    public function getOptions(): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $this->service->getOptions(),
        ], 200);
    }

    /**
     * 测试过滤.
     *
     * @authenticated
     *
     * @bodyParam content string required 测试内容
     */
    public function testFilter(TestFilterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->service->filter($data['content'], 'test');

        return response()->json([
            'code' => 200,
            'message' => '测试完成',
            'data' => $result,
        ], 200);
    }

    /**
     * 获取命中日志.
     *
     * @authenticated
     *
     * @queryParam page int 页码
     * @queryParam per_page int 每页数量（支持驼峰格式 perPage）
     * @queryParam content_type string 内容类型筛选（支持驼峰格式 contentType）
     * @queryParam user_id int 用户ID筛选（支持驼峰格式 userId）
     */
    public function logs(Request $request): JsonResponse
    {
        $query = SensitiveWordLog::with(['sensitiveWord:id,word,category', 'user:id,username,nickname']);

        // 支持驼峰格式的查询参数
        $contentType = $request->input('content_type') ?? $request->input('contentType');
        $userId = $request->input('user_id') ?? $request->input('userId');

        if ($contentType) {
            $query->where('content_type', $contentType);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $query->orderBy('created_at', 'desc');

        // 使用统一的分页参数处理（支持驼峰格式 perPage）
        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        $paginationParams = [
            'per_page' => $perPage,
            'page' => $request->input('page'),
        ];
        $perPage = $paginationParams['per_page'];
        $page = $paginationParams['page'] ?? null;

        if ($page !== null) {
            $logs = $query->paginate($perPage, ['*'], 'page', $page);
        } else {
            $logs = $query->paginate($perPage);
        }

        // 返回Laravel原生分页格式
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => SensitiveWordLogResource::collection($logs->items()),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
                'prev_page_url' => $logs->previousPageUrl(),
                'next_page_url' => $logs->nextPageUrl(),
            ]
        ]);
    }
}
