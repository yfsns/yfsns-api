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

namespace App\Modules\SensitiveWord\Services;

use App\Modules\SensitiveWord\Models\SensitiveWord;
use App\Modules\SensitiveWord\Models\SensitiveWordLog;
use DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SensitiveWordService
{
    /**
     * 缓存键.
     */
    public const CACHE_KEY = 'sensitive_words_cache';

    /**
     * 获取缓存时间（分钟）
     */
    protected function getCacheMinutes(): int
    {
        return config('sensitive_word.cache_minutes', 60);
    }

    /**
     * 获取内容类型的处理动作
     */
    protected function getContentTypeAction(string $contentType): string
    {
        $contentTypes = config('sensitive_word.content_types', []);

        return $contentTypes[$contentType]['action'] ?? config('sensitive_word.default_action', 'replace');
    }

    /**
     * 检查是否启用敏感词过滤
     */
    protected function isEnabled(): bool
    {
        return config('sensitive_word.enabled', true);
    }

    /**
     * 检查内容类型是否启用过滤
     */
    protected function isContentTypeEnabled(string $contentType): bool
    {
        $contentTypes = config('sensitive_word.content_types', []);

        return $contentTypes[$contentType]['enabled'] ?? true;
    }

    /**
     * 过滤内容中的敏感词.
     *
     * @param string   $content     原始内容
     * @param string   $contentType 内容类型（post/comment/thread/nickname）
     * @param null|int $contentId   内容ID
     * @param null|int $userId      用户ID
     *
     * @return array ['filtered' => 过滤后内容, 'action' => 动作, 'words' => 命中的敏感词]
     */
    public function filter(string $content, string $contentType = 'post', ?int $contentId = null, ?int $userId = null): array
    {
        // 检查是否启用敏感词过滤
        if (!$this->isEnabled()) {
            return [
                'filtered' => $content,
                'action' => 'pass',
                'words' => [],
                'hasSensitive' => false,
            ];
        }

        // 检查内容类型是否启用过滤
        if (!$this->isContentTypeEnabled($contentType)) {
            return [
                'filtered' => $content,
                'action' => 'pass',
                'words' => [],
                'hasSensitive' => false,
            ];
        }

        $sensitiveWords = $this->getActiveSensitiveWords();

        if ($sensitiveWords->isEmpty()) {
            return [
                'filtered' => $content,
                'action' => 'pass',
                'words' => [],
                'hasSensitive' => false,
            ];
        }

        $filteredContent = $content;
        $hitWords = [];
        $finalAction = 'pass';

        foreach ($sensitiveWords as $word) {
            $result = $this->checkWord($filteredContent, $word);

            if ($result['hit']) {
                $hitWords[] = [
                    'id' => $word->id,
                    'word' => $word->word,
                    'category' => $word->category,
                    'level' => $word->level,
                    'action' => $word->action,
                ];

                // 根据敏感词设置决定最终动作
                if ($word->action === SensitiveWord::ACTION_REJECT) {
                    $finalAction = 'reject';
                } elseif ($word->action === SensitiveWord::ACTION_REVIEW && $finalAction !== 'reject') {
                    $finalAction = 'review';
                } elseif ($word->action === SensitiveWord::ACTION_REPLACE && $finalAction === 'pass') {
                    $finalAction = 'replace';
                }

                // 替换敏感词
                if ($word->action === SensitiveWord::ACTION_REPLACE) {
                    $replacement = $word->replacement ?: '***';
                    $filteredContent = $result['replaced'];
                }

                // 记录命中日志
                $this->logHit($word, $contentType, $contentId, $userId, $content, $filteredContent);

                // 增加命中次数
                $word->incrementHitCount();
            }
        }

        return [
            'filtered' => $filteredContent,
            'action' => $finalAction,
            'words' => $hitWords,
            'hasSensitive' => ! empty($hitWords),
        ];
    }

    /**
     * 清除敏感词缓存.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * 批量导入敏感词.
     */
    public function batchImport(array $words, string $category = 'other', string $level = 'medium', string $action = 'replace'): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($words as $word) {
            SensitiveWord::updateOrCreate(
                ['word' => trim($word)],
                [
                    'category' => $category,
                    'level' => $level,
                    'action' => $action,
                    'status' => true,
                    'created_by' => auth()->id(),
                ]
            );
            $imported++;
        }

        $this->clearCache();

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * 获取统计数据.
     */
    public function getStats(): array
    {
        return [
            'total' => SensitiveWord::count(),
            'active' => SensitiveWord::where('status', true)->count(),
            'inactive' => SensitiveWord::where('status', false)->count(),
            'byCategory' => SensitiveWord::select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
            'byLevel' => SensitiveWord::select('level', DB::raw('count(*) as count'))
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray(),
            'topHit' => SensitiveWord::orderBy('hit_count', 'desc')
                ->limit(10)
                ->get(['word', 'hit_count', 'category'])
                ->map(function ($item) {
                    return [
                        'word' => $item->word,
                        'hitCount' => $item->hit_count,
                        'category' => $item->category,
                    ];
                })
                ->toArray(),
        ];
    }

    /**
     * 获取配置选项（前端友好格式）.
     */
    public function getOptions(): array
    {
        $formatOptions = fn (array $options) => collect($options)
            ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->toArray();

        return [
            'categories' => $formatOptions(SensitiveWord::getCategories()),
            'levels' => $formatOptions(SensitiveWord::getLevels()),
            'actions' => $formatOptions(SensitiveWord::getActions()),
        ];
    }

    /**
     * 检查单个敏感词.
     */
    protected function checkWord(string $content, SensitiveWord $word): array
    {
        $pattern = $word->is_regex ? $word->word : '/' . preg_quote($word->word, '/') . '/iu';

        if (preg_match($pattern, $content)) {
            $replacement = $word->replacement ?: '***';
            $replaced = preg_replace($pattern, $replacement, $content);

            return [
                'hit' => true,
                'replaced' => $replaced,
            ];
        }

        return [
            'hit' => false,
            'replaced' => $content,
        ];
    }

    /**
     * 记录命中日志.
     */
    protected function logHit(SensitiveWord $word, string $contentType, ?int $contentId, ?int $userId, string $original, string $filtered): void
    {
        SensitiveWordLog::create([
            'sensitive_word_id' => $word->id,
            'content_type' => $contentType,
            'content_id' => $contentId,
            'user_id' => $userId,
            'original_content' => mb_substr($original, 0, 500), // 截取前500字符
            'filtered_content' => mb_substr($filtered, 0, 500),
            'action' => $word->action,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * 获取启用的敏感词（带缓存）.
     */
    protected function getActiveSensitiveWords()
    {
        return Cache::remember(self::CACHE_KEY, $this->getCacheMinutes() * 60, function () {
            return SensitiveWord::where('status', true)
                ->orderBy('level', 'desc') // 高级别优先
                ->orderBy('category')
                ->get();
        });
    }
}
