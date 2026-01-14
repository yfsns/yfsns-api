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

namespace App\Modules\System\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 网站配置资源类
 */
class WebsiteConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => (string) $this->id,
            'siteName' => $this->site_name,
            'siteUrl' => $this->site_url,
            'siteDescription' => $this->site_description,
            'siteKeywords' => $this->site_keywords,
            'siteTitle' => $this->site_title,
            'siteLogo' => $this->site_logo,
            'siteHeaderLogo' => $this->site_header_logo,
            'siteFavicon' => $this->site_favicon,
            'siteStatus' => $this->site_status,
            'icpNumber' => $this->icp_number,
            'policeRecord' => $this->police_record,
            'statisticsCode' => $this->statistics_code,
        ];
    }
}
