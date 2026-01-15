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

namespace App\Modules\System\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\System\Requests\UpdateWebsiteConfigRequest;
use App\Modules\System\Resources\WebsiteConfigResource;
use App\Modules\System\Services\WebsiteConfigService;
use Illuminate\Http\JsonResponse;

/**
 * @group admin-后台管理-系统配置
 *
 * @name 后台管理-系统配置
 */
class WebsiteConfigController extends Controller
{
    protected $service;

    public function __construct(WebsiteConfigService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取网站配置.
     *
     * @authenticated
     */
    public function show(): JsonResponse
    {
        $data = $this->service->getConfig();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => new WebsiteConfigResource($data),
        ], 200);
    }

    /**
     * 更新网站配置.
     *
     * @authenticated
     */
    public function update(UpdateWebsiteConfigRequest $request): JsonResponse
    {
        $data = $this->service->update($request->validated());

        return response()->json([
            'code' => 200,
            'message' => '更新成功',
            'data' => new WebsiteConfigResource($data),
        ], 200);
    }

}
