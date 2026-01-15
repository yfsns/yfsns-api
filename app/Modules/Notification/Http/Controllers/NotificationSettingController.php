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
use App\Modules\Notification\Http\Requests\StoreNotificationSettingRequest;
use App\Modules\Notification\Http\Requests\UpdateNotificationSettingRequest;
use App\Modules\Notification\Models\NotificationSetting;
use App\Modules\Notification\Resources\NotificationSettingResource;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{

    /**
     * 获取设置列表.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知设置管理
     *
     * @authenticated
     *
     * @queryParam type string 通知类型（system,business,social）
     * @queryParam notifiable_type string 通知对象类型
     * @queryParam notifiable_id integer 通知对象ID
     * @queryParam per_page integer 每页数量，默认15
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "notifiable_type": "App\\Models\\User",
     *       "notifiable_id": 1,
     *       "type": "system",
     *       "channels": ["database"],
     *       "preferences": {},
     *       "created_at": "2024-03-20T00:00:00.000000Z",
     *       "updated_at": "2024-03-20T00:00:00.000000Z"
     *     }
     *   ],
     *   "current_page": 1,
     *   "per_page": 15,
     *   "total": 1
     * }
     */
    public function index(Request $request)
    {
        $settings = NotificationSetting::query()
            ->when($request->input('type'), function ($query, $type): void {
                $query->where('type', $type);
            })
            ->when($request->input('notifiable_type'), function ($query, $type): void {
                $query->where('notifiable_type', $type);
            })
            ->when($request->input('notifiable_id'), function ($query, $id): void {
                $query->where('notifiable_id', $id);
            })
            ->paginate($request->input('per_page', 15));

        // 返回Laravel原生分页格式
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'data' => NotificationSettingResource::collection($settings->items()),
                'current_page' => $settings->currentPage(),
                'last_page' => $settings->lastPage(),
                'per_page' => $settings->perPage(),
                'total' => $settings->total(),
                'from' => $settings->firstItem(),
                'to' => $settings->lastItem(),
                'prev_page_url' => $settings->previousPageUrl(),
                'next_page_url' => $settings->nextPageUrl(),
            ]
        ]);
    }

    /**
     * 创建设置.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知设置管理
     *
     * @authenticated
     *
     * @bodyParam notifiable_type string required 通知对象类型
     * @bodyParam notifiable_id integer required 通知对象ID
     * @bodyParam type string required 通知类型（system,business,social）
     * @bodyParam channels array required 启用的通道（mail,sms,wechat,database）
     * @bodyParam preferences array 通知偏好设置
     *
     * @response 201 {
     *   "id": 1,
     *   "notifiable_type": "App\\Models\\User",
     *   "notifiable_id": 1,
     *   "type": "system",
     *   "channels": ["database"],
     *   "preferences": {},
     *   "created_at": "2024-03-20T00:00:00.000000Z",
     *   "updated_at": "2024-03-20T00:00:00.000000Z"
     * }
     */
    public function store(StoreNotificationSettingRequest $request)
    {
        $validated = $request->validated();

        $setting = NotificationSetting::create($validated);

        return response()->success($setting, '通知设置创建成功', 201);
    }

    /**
     * 获取设置详情.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知设置管理
     *
     * @authenticated
     *
     * @response {
     *   "id": 1,
     *   "notifiable_type": "App\\Models\\User",
     *   "notifiable_id": 1,
     *   "type": "system",
     *   "channels": ["database"],
     *   "preferences": {},
     *   "created_at": "2024-03-20T00:00:00.000000Z",
     *   "updated_at": "2024-03-20T00:00:00.000000Z"
     * }
     */
    public function show(NotificationSetting $setting)
    {
        return response()->success($setting, '获取通知设置成功');
    }

    /**
     * 更新设置.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知设置管理
     *
     * @authenticated
     *
     * @bodyParam type string 通知类型（system,business,social）
     * @bodyParam channels array 启用的通道（mail,sms,wechat,database）
     * @bodyParam preferences array 通知偏好设置
     *
     * @response {
     *   "id": 1,
     *   "notifiable_type": "App\\Models\\User",
     *   "notifiable_id": 1,
     *   "type": "system",
     *   "channels": ["database"],
     *   "preferences": {},
     *   "created_at": "2024-03-20T00:00:00.000000Z",
     *   "updated_at": "2024-03-20T00:00:00.000000Z"
     * }
     */
    public function update(UpdateNotificationSettingRequest $request, NotificationSetting $setting)
    {
        $validated = $request->validated();

        $setting->update($validated);

        return response()->success($setting, '通知设置更新成功');
    }

    /**
     * 删除设置.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @group 通知设置管理
     *
     * @authenticated
     *
     * @response {
     *   "message": "设置已删除"
     * }
     */
    public function destroy(NotificationSetting $setting)
    {
        $setting->delete();

        return response()->json(['message' => '设置已删除']);
    }
}
