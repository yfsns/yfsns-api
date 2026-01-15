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

namespace App\Modules\Report\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Report\Resources\ReportResource;
use App\Modules\Report\Services\AdminReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group admin-后台管理-举报管理
 *
 * @name 后台管理-举报管理
 *
 * @description 处理系统举报相关功能的后台管理接口
 */
class ReportController extends Controller
{

    protected $service;

    public function __construct(AdminReportService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取举报列表.
     *
     * @authenticated
     *
     * @queryParam keyword string 搜索关键词
     * @queryParam status int 状态
     * @queryParam sort_field string 排序字段
     * @queryParam sort_order string 排序方式（asc/desc）
     * @queryParam per_page int 每页数量（支持：10、20、50、100）
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "list": [],
     *     "total": 0,
     *     "page": 1,
     *     "per_page": 10
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 15;
        }

        $params = [
            'per_page' => $perPage,
            'page' => $request->input('page'),
        ];
        $reports = $this->service->getList($params);

        // 返回Laravel原生分页格式
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => ReportResource::collection($reports->items()),
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'from' => $reports->firstItem(),
                'to' => $reports->lastItem(),
                'prev_page_url' => $reports->previousPageUrl(),
                'next_page_url' => $reports->nextPageUrl(),
            ]
        ]);
    }

    /**
     * 获取举报详情.
     *
     * @authenticated
     *
     * @param int $id 举报ID 必传
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "id": 1,
     *     "content": "举报内容",
     *     "status": "待处理",
     *     "created_at": "2024-03-21 10:00:00"
     *   }
     * }
     */
    public function show(int $id): JsonResponse
    {
        $data = $this->service->getDetail($id);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $data,
        ], 200);
    }

    /**
     * 处理举报.
     *
     * @authenticated
     *
     * @param Request $request 请求对象
     * @param int     $id      举报ID 必传
     *
     * @response {
     *   "code": 200,
     *   "message": "处理成功",
     *   "data": {
     *     "id": 1,
     *     "status": "已处理",
     *     "handled_at": "2024-03-21 10:00:00"
     *   }
     * }
     */
    public function handle(Request $request, int $id): JsonResponse
    {
        $data = $this->service->handle($id, $request->all());

        return response()->json([
            'code' => 200,
            'message' => '处理成功',
            'data' => $data,
        ], 200);
    }

    /**
     * 批量处理举报.
     *
     * @authenticated
     *
     * @param Request $request 请求对象
     *
     * @response {
     *   "code": 200,
     *   "message": "批量处理成功",
     *   "data": {
     *     "success_count": 5,
     *     "failed_count": 0
     *   }
     * }
     */
    public function batchHandle(Request $request): JsonResponse
    {
        $data = $this->service->batchHandle($request->all());

        return response()->json([
            'code' => 200,
            'message' => '批量处理成功',
            'data' => $data,
        ], 200);
    }

    /**
     * 导出举报数据.
     *
     * @authenticated
     *
     * @param Request $request 请求对象
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        return $this->service->export($request->all());
    }

    /**
     * 获取举报统计数据.
     *
     * @authenticated
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "total": 100,
     *     "pending": 20,
     *     "in_progress": 5,
     *     "handled": 70,
     *     "rejected": 10,
     *     "today_count": 5
     *   }
     * }
     */
    public function statistics(): JsonResponse
    {
        $data = $this->service->getStatistics();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $data,
        ], 200);
    }
}
