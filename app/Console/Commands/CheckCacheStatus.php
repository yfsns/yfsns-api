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

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;

class CheckCacheStatus extends Command
{
    protected $signature = 'cache:status';

    protected $description = '检查当前缓存系统状态';

    public function handle(CacheService $cacheService): void
    {
        $this->info('当前缓存驱动: ' . $cacheService->getDriver());
        $this->info('是否使用 Redis: ' . ($cacheService->isUsingRedis() ? '是' : '否'));

        // 测试缓存功能
        $testKey = 'cache_test_' . time();
        $testValue = 'test_value';

        $this->info("\n测试缓存功能:");

        // 测试写入
        $this->info('写入缓存...');
        $writeResult = $cacheService->put($testKey, $testValue, 60);
        $this->info('写入结果: ' . ($writeResult ? '成功' : '失败'));

        // 测试读取
        $this->info('读取缓存...');
        $readValue = $cacheService->get($testKey);
        $this->info('读取结果: ' . ($readValue === $testValue ? '成功' : '失败'));

        // 测试删除
        $this->info('删除缓存...');
        $deleteResult = $cacheService->forget($testKey);
        $this->info('删除结果: ' . ($deleteResult ? '成功' : '失败'));

        // 清理测试数据
        $cacheService->forget($testKey);
    }
}
