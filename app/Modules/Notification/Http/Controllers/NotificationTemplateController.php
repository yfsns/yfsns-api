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

namespace App\Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notification\Http\Requests\UpdateNotificationTemplateRequest;
use App\Modules\Notification\Models\NotificationTemplate;
use App\Modules\Notification\Resources\NotificationTemplateResource;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{

    /**
     * 获取模板列表.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知模板管理
     *
     * @authenticated
     *
     * @queryParam type string 通知类型（system,business,social,email）
     * @queryParam status boolean 状态（0禁用，1启用）
     * @queryParam per_page integer 每页数量，默认15
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "list": [...],
     *     "page": 1,
     *     "perPage": 15,
     *     "total": 1,
     *     "lastPage": 1,
     *     "hasMore": false
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $paginationParams = [
            'per_page' => (int) ($request->input('per_page', 15)),
            'page' => $request->input('page'),
        ];

        $query = NotificationTemplate::query()
            ->when($request->input('type'), function ($query, $type): void {
                $query->where('type', $type);
            })
            ->when($request->input('category'), function ($query, $category): void {
                $query->where('category', $category);
            })
            ->when($request->input('status') !== null, function ($query) use ($request): void {
                $query->where('status', $request->input('status'));
            })
            ->when($request->input('priority'), function ($query, $priority): void {
                $query->where('priority', $priority);
            })
            ->orderBy('priority', 'desc')
            ->orderBy('id', 'desc');

        // 分页参数处理
        $perPage = (int) ($paginationParams['per_page'] ?? 15);
        $page = $paginationParams['page'] ?? null;

        if ($page !== null) {
            $templates = $query->paginate($perPage, ['*'], 'page', $page);
        } else {
            $templates = $query->paginate($perPage);
        }

        // 返回分页结果（保持壳结构）
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => NotificationTemplateResource::collection($templates->items()),
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
                'from' => $templates->firstItem(),
                'to' => $templates->lastItem(),
                'prev_page_url' => $templates->previousPageUrl(),
                'next_page_url' => $templates->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * 获取模板详情.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知模板管理
     *
     * @authenticated
     *
     * @urlParam id integer required 模板ID. Example: 1
     *
     * @response {
     *   "code": 200,
     *   "message": "获取成功",
     *   "data": {
     *     "id": 1,
     *     "code": "user_register_success",
     *     "name": "用户注册成功",
     *     "type": "system",
     *     "channels": ["database"],
     *     "content": {"database": "欢迎 {username}，注册成功！"},
     *     "variables": ["username"],
     *     "status": 1,
     *     "created_at": "2024-03-20T00:00:00.000000Z",
     *     "updated_at": "2024-03-20T00:00:00.000000Z"
     *   }
     * }
     */
    public function show($id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => new NotificationTemplateResource($template),
        ], 200);
    }

    /**
     * 更新模板
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知模板管理
     *
     * @authenticated
     *
     * @urlParam id integer required 模板ID. Example: 1
     *
     * @bodyParam name string 模板名称，最大100个字符
     * @bodyParam type string 通知类型（system,email,sms）
     * @bodyParam category string 分类（general,user,order,security,social）
     * @bodyParam channels array 支持的通道（database,mail,sms）
     * @bodyParam content array 模板内容，每个通道对应一个内容
     * @bodyParam variables array 模板变量
     * @bodyParam sms_template_id string 短信模板ID（可选）
     * @bodyParam status boolean 状态（0禁用，1启用）
     * @bodyParam priority integer 优先级（1,2,3）
     * @bodyParam remark string 备注（可选）
     *
     * @response {
     *   "code": 200,
     *   "message": "更新成功",
     *   "data": {
     *     "id": 1,
     *     "code": "user_register_success",
     *     "name": "用户注册成功",
     *     "type": "system",
     *     "channels": ["database"],
     *     "content": {"database": "欢迎 {username}，注册成功！"},
     *     "variables": ["username"],
     *     "status": 1,
     *     "created_at": "2024-03-20T00:00:00.000000Z",
     *     "updated_at": "2024-03-20T00:00:00.000000Z"
     *   }
     * }
     */
    public function update(UpdateNotificationTemplateRequest $request, $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);

        $template->update($request->validated());

        return response()->json([
            'code' => 200,
            'message' => '更新成功',
            'data' => new NotificationTemplateResource($template),
        ], 200);
    }

    /**
     * 删除模板
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知模板管理
     *
     * @authenticated
     *
     * @urlParam id integer required 模板ID. Example: 1
     *
     * @response {
     *   "code": 200,
     *   "message": "删除成功"
     * }
     */
    public function destroy($id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->delete();

        return response()->json([
            'code' => 200,
            'message' => '删除成功',
            'data' => null,
        ], 200);
    }
}
