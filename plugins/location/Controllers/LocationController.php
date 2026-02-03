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

namespace Plugins\Location\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Plugins\Location\Http\Requests\CalculateDistanceRequest;
use Plugins\Location\Http\Requests\GeocodeRequest;
use Plugins\Location\Http\Requests\GetLocationByIpRequest;
use Plugins\Location\Http\Requests\ReverseGeocodeRequest;
use Plugins\Location\Services\LocationService;
use Illuminate\Http\JsonResponse;

/**
 * 定位服务控制器.
 */
class LocationController extends Controller
{
    protected LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * 逆地理编码（坐标转地址）.
     *
     * @api GET /api/v1/location/reverse
     *
     * @param float lat 纬度
     * @param float lng 经度
     * @param string driver 驱动名称（可选）
     */
    public function reverseGeocode(ReverseGeocodeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->locationService->reverseGeocode(
            $data['lat'],
            $data['lng'],
            $data['driver'] ?? null
        );

        if (! $result->success) {
            return response()->json([
                'code' => 500,
                'message' => $result->error,
                'data' => null,
            ], 500);
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $result->toArray(),
        ], 200);
    }

    /**
     * 地理编码（地址转坐标）.
     *
     * @api GET /api/v1/location/geocode
     *
     * @param string address 地址
     * @param string city 城市（可选）
     * @param string driver 驱动名称（可选）
     */
    public function geocode(GeocodeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->locationService->geocode(
            $data['address'],
            $data['city'] ?? null,
            $data['driver'] ?? null
        );

        if (! $result->success) {
            return response()->json([
                'code' => 500,
                'message' => $result->error,
                'data' => null,
            ], 500);
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $result->toArray(),
        ], 200);
    }

    /**
     * IP定位.
     *
     * @api GET /api/v1/location/ip
     *
     * @param string ip IP地址（可选，默认使用请求IP）
     * @param string driver 驱动名称（可选）
     */
    public function getLocationByIp(GetLocationByIpRequest $request): JsonResponse
    {
        $data = $request->validated();

        $ip = $data['ip'] ?? $request->ip();

        $result = $this->locationService->getLocationByIp(
            $ip,
            $data['driver'] ?? null
        );

        if (! $result->success) {
            return response()->json([
                'code' => 500,
                'message' => $result->error,
                'data' => null,
            ], 500);
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $result->toArray(),
        ], 200);
    }

    /**
     * 计算两点距离.
     *
     * @api GET /api/v1/location/distance
     *
     * @param float lat1 点1纬度
     * @param float lng1 点1经度
     * @param float lat2 点2纬度
     * @param float lng2 点2经度
     * @param string driver 驱动名称（可选）
     */
    public function calculateDistance(CalculateDistanceRequest $request): JsonResponse
    {
        $data = $request->validated();

        $distance = $this->locationService->calculateDistance(
            $data['lat1'],
            $data['lng1'],
            $data['lat2'],
            $data['lng2'],
            $data['driver'] ?? null
        );

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'distance' => $distance,
                'unit' => 'meters',
            ],
        ], 200);
    }

    /**
     * 获取可用驱动列表.
     *
     * @api GET /api/v1/location/drivers
     */
    public function getDrivers(): JsonResponse
    {
        $drivers = $this->locationService->getAvailableDrivers();

        // 从插件配置系统获取默认驱动
        try {
            $pluginConfigManager = app(\App\Modules\PluginSystem\Services\PluginConfigManagerService::class);
            $defaultDriver = $pluginConfigManager->getPluginConfigValue('Location', 'LOCATION_DEFAULT_DRIVER', 'tencent');
        } catch (Exception $e) {
            $defaultDriver = 'tencent';
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'drivers' => $drivers,
                'default' => $defaultDriver,
            ],
        ], 200);
    }

    /**
     * 清除缓存.
     *
     * @api POST /api/v1/location/cache/clear
     */
    public function clearCache(): JsonResponse
    {
        $this->locationService->clearCache();

        return response()->json([
            'code' => 200,
            'message' => '缓存已清除',
            'data' => null,
        ], 200);
    }
}
