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

namespace App\Modules\SensitiveWord\Database\Seeders;

use App\Modules\SensitiveWord\Models\SensitiveWord;
use Illuminate\Database\Seeder;

class SensitiveWordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('开始填充敏感词数据...');

        $sensitiveWords = [
            // 保留3条基本的脏话敏感词
            ['word' => '傻逼', 'category' => 'other', 'level' => 'medium', 'action' => 'replace', 'replacement' => '**', 'description' => '不当言论'],
            ['word' => '操你妈', 'category' => 'other', 'level' => 'high', 'action' => 'reject', 'description' => '不当言论'],
            ['word' => '王八蛋', 'category' => 'other', 'level' => 'medium', 'action' => 'replace', 'replacement' => '**', 'description' => '不当言论'],
        ];

        $createdCount = 0;
        $updatedCount = 0;
        $failedCount = 0;

        foreach ($sensitiveWords as $wordData) {
            // 确保新创建的敏感词默认启用
            if (!isset($wordData['status'])) {
                $wordData['status'] = true;
            }

            try {
                $word = SensitiveWord::updateOrCreate(
                    ['word' => $wordData['word']], // 查找条件：根据 word 字段
                    $wordData // 创建或更新的数据
                );

                if ($word->wasRecentlyCreated) {
                    $createdCount++;
                } else {
                    $updatedCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
                $this->command->error("✗ 处理敏感词失败: {$wordData['word']} ({$wordData['category']}) - {$e->getMessage()}");
            }
        }

        $this->command->info("敏感词填充完成：新建 {$createdCount} 条，更新 {$updatedCount} 条，失败 {$failedCount} 条");
        if ($failedCount > 0) {
            $this->command->warn('部分敏感词处理失败，请检查错误信息');
        }
    }
}
