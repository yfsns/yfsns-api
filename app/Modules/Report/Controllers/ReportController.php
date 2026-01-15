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

namespace App\Modules\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Requests\StoreReportRequest;
use App\Modules\Report\Services\UserReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 举报模块
 *
 * @name 举报模块
 *
 * @description 用户举报相关功能接口
 */
class ReportController extends Controller
{
    protected $service;

    public function __construct(UserReportService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取用户的举报历史.
     *
     * @authenticated
     *
     * @queryParam type string 举报类型（post/comment/user）. Example: post
     * @queryParam status string 举报状态. Example: pending
     * @queryParam page integer 页码. Example: 1
     * @queryParam per_page integer 每页数量. Example: 15
     * @queryParam order_by string 排序字段. Example: created_at
     * @queryParam order_direction string 排序方向. Example: desc
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "list": [
     *       {
     *         "id": 1,
     *         "type": "post",
     *         "target_id": 123,
     *         "reason": "违规内容",
     *         "status": "pending",
     *         "created_at": "2024-03-21 10:00:00"
     *       }
     *     ],
     *     "total": 1,
     *     "page": 1,
     *     "per_page": 15
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getHistory($request->all());

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $data,
        ], 200);
    }

    /**
     * 提交举报.
     *
     * @authenticated
     *
     * @bodyParam type string required 举报类型（post/comment/user）. Example: post
     * @bodyParam target_id integer required 被举报对象ID. Example: 123
     * @bodyParam reason string required 举报原因. Example: 违规内容
     * @bodyParam description string 详细描述. Example: 该内容违反了社区规范
     * @bodyParam evidence array 证据材料
     * @bodyParam evidence.images array 图片证据
     * @bodyParam evidence.images.* file 图片文件（最大5MB）
     * @bodyParam evidence.videos array 视频证据
     * @bodyParam evidence.videos.* file 视频文件（最大50MB）
     * @bodyParam evidence.others array 其他证据
     *
     * @response {
     *   "code": 200,
     *   "message": "举报成功",
     *   "data": {
     *     "id": 1,
     *     "type": "post",
     *     "target_id": 123,
     *     "reason": "违规内容",
     *     "status": "pending",
     *     "created_at": "2024-03-21 10:00:00"
     *   }
     * }
     * @response 422 {
     *   "code": 422,
     *   "message": "验证失败",
     *   "data": {
     *     "type": ["举报类型不能为空"],
     *     "target_id": ["被举报对象ID不能为空"]
     *   }
     * }
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $data = $request->validated();

        $report = $this->service->create($data);

        return response()->json([
            'code' => 200,
            'message' => '举报成功',
            'data' => $report,
        ], 200);
    }

    /**
     * 获取举报详情.
     *
     * @authenticated
     *
     * @urlParam id integer required 举报ID. Example: 1
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "id": 1,
     *     "type": "post",
     *     "target_id": 123,
     *     "reason": "违规内容",
     *     "description": "该内容违反了社区规范",
     *     "status": "pending",
     *     "created_at": "2024-03-21 10:00:00",
     *     "evidence": {
     *       "images": ["url1", "url2"],
     *       "videos": ["url1"]
     *     }
     *   }
     * }
     * @response 404 {
     *   "code": 404,
     *   "message": "举报不存在",
     *   "data": null
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
     * 取消举报.
     *
     * @authenticated
     *
     * @urlParam id integer required 举报ID. Example: 1
     *
     * @response {
     *   "code": 200,
     *   "message": "取消成功",
     *   "data": {
     *     "id": 1,
     *     "type": "post",
     *     "target_id": 123,
     *     "status": "cancelled",
     *     "cancelled_at": "2024-03-21 10:00:00"
     *   }
     * }
     * @response 404 {
     *   "code": 404,
     *   "message": "举报不存在",
     *   "data": null
     * }
     * @response 400 {
     *   "code": 400,
     *   "message": "只能取消待处理的举报",
     *   "data": null
     * }
     */
    public function cancel(int $id): JsonResponse
    {
        $data = $this->service->cancel($id);

        return response()->json([
            'code' => 200,
            'message' => '取消成功',
            'data' => $data,
        ], 200);
    }
}
