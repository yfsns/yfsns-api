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

namespace App\Modules\Location\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * 定位服务门面.
 *
 * @method static \App\Modules\Location\DTOs\LocationResponse             reverseGeocode(float $latitude, float $longitude, ?string $driver = null, bool $useCache = true)
 * @method static \App\Modules\Location\DTOs\LocationResponse             geocode(string $address, ?string $city = null, ?string $driver = null, bool $useCache = true)
 * @method static \App\Modules\Location\DTOs\LocationResponse             getLocationByIp(string $ip, ?string $driver = null, bool $useCache = true)
 * @method static float                                                   calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2, ?string $driver = null)
 * @method static \App\Modules\Location\Contracts\LocationDriverInterface driver(?string $name = null)
 * @method static array                                                   getAvailableDrivers()
 * @method static void                                                    clearCache(?string $pattern = null)
 *
 * @see \App\Modules\Location\Services\LocationService
 */
class Location extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'location';
    }
}
