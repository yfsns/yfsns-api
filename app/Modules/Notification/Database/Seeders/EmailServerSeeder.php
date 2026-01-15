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

namespace App\Modules\Notification\Database\Seeders;

use App\Modules\Notification\Models\EmailConfig;
use Illuminate\Database\Seeder;

class EmailServerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 只创建一条默认配置记录
        EmailConfig::create([
            'driver' => 'smtp', 
            'host' => 'smtp.qq.com',
            'port' => 465,
            'encryption' => 'ssl',
            'from_address' => 'noreply@example.com',
            'from_name' => 'YFSNS官方',
        ]);
    }
}
