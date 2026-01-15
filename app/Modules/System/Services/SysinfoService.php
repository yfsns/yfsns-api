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

namespace App\Modules\System\Services;

use App\Modules\Post\Models\Post;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use function ini_get;

use PDO;

use const PHP_VERSION;

use Throwable;

class SysinfoService
{

    public function getSystemInfo(): array
    {
        $connection = config('database.default');
        $connectionConfig = config("database.connections.$connection", []);
        $databaseType = $connectionConfig['driver'] ?? $connection;

        return [
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
            'databaseConnection' => $connection,
            'databaseType' => $databaseType,
            'databaseVersion' => $this->getDatabaseVersion(),
            'serverOs' => php_uname('s') . ' ' . php_uname('r'),
            'serverSoftware' => $_SERVER['SERVER_SOFTWARE'] ?? php_sapi_name(),
            'memoryLimit' => ini_get('memory_limit'),
            'maxUploadSize' => ini_get('upload_max_filesize'),
            'postMaxSize' => ini_get('post_max_size'),
            'timezone' => config('app.timezone'),
            'appEnv' => config('app.env'),
        ];
    }

    public function getAnalysisData(): array
    {
        $days = collect();
        $start = Carbon::now()->subDays(6)->startOfDay();

        for ($date = $start->copy(); $date->lte(Carbon::now()); $date->addDay()) {
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $days->push([
                'date' => $date->format('Y-m-d'),
                'newUsers' => User::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'newPosts' => Post::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
            ]);
        }

        return [
            'range' => $days,
        ];
    }

    public function getUserStats(): array
    {
        $total = User::count();
        $active = User::active()->count(); // 使用 scope
        $inactive = User::disabled()->count(); // 使用 scope
        $newToday = User::where('created_at', '>=', Carbon::today())->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'newToday' => $newToday,
        ];
    }


    public function getUserGrowth(): array
    {
        $data = [];
        $start = Carbon::now()->subDays(14)->startOfDay();

        for ($date = $start->copy(); $date->lte(Carbon::now()); $date->addDay()) {
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'value' => User::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
            ];
        }

        return $data;
    }

    protected function getDatabaseVersion(): ?string
    {
        try {
            $pdo = DB::connection()->getPdo();

            return $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (Throwable $e) {
            return null;
        }
    }
}
