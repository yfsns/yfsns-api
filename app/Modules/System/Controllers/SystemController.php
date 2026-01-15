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

namespace App\Modules\System\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Services\WebsiteConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * @group 系统模块
 *
 * @name 系统模块
 */
class SystemController extends Controller
{
    protected $websiteConfigService;

    public function __construct(WebsiteConfigService $websiteConfigService)
    {
        $this->websiteConfigService = $websiteConfigService;
    }

    /**
     * 获取网站信息.
     *
     * 返回网站的基本信息，包括网站名称、描述、URL等
     */
    public function websiteInfo(): JsonResponse
    {
        $data = Cache::remember('website_info_response', 300, function () {
            $config = $this->websiteConfigService->getConfig();

            return [
                'siteName' => $config->site_name,
                'siteUrl' => $config->site_url,
                'siteDescription' => $config->site_description,
                'siteKeywords' => $config->site_keywords,
                'siteTitle' => $config->site_title,
                'siteLogo' => $config->site_logo,
                'siteHeaderLogo' => $config->site_header_logo,
                'siteFavicon' => $config->site_favicon,
                'siteStatus' => $config->site_status,
                'icpNumber' => $config->icp_number,
                'policeRecord' => $config->police_record,
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => '获取网站信息成功',
            'data' => $data,
        ], 200);
    }
}
