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
use App\Modules\System\Services\SysinfoService;
use Illuminate\Http\JsonResponse;

class SysinfoController extends Controller
{
    protected $sysinfoService;

    public function __construct(SysinfoService $sysinfoService)
    {
        $this->sysinfoService = $sysinfoService;
    }

    /**
     * 获取系统信息
     *
     * @authenticated
     */
    public function systemInfo(): JsonResponse
    {
        $data = [
            'system' => $this->sysinfoService->getSystemInfo(),
            'analysis' => $this->sysinfoService->getAnalysisData(),
            'userStats' => $this->sysinfoService->getUserStats(),
            'userGrowth' => $this->sysinfoService->getUserGrowth(),
        ];

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $data,
        ], 200);
    }
}
