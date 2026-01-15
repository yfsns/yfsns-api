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

namespace App\Http\Traits;

use App\Exceptions\BusinessException;
use App\Modules\SensitiveWord\Services\SensitiveWordService;

trait SensitiveWordFilterTrait
{
    /**
     * 过滤内容中的敏感词.
     *
     * @param string   $content     内容
     * @param string   $contentType 内容类型
     * @param null|int $contentId   内容ID
     *
     * @throws BusinessException
     *
     * @return string 过滤后的内容
     */
    protected function filterSensitiveWords(string $content, string $contentType = 'post', ?int $contentId = null): string
    {
        $service = app(SensitiveWordService::class);
        $result = $service->filter($content, $contentType, $contentId, \Auth::id());

        // 如果动作是拒绝，抛出异常
        if ($result['action'] === 'reject') {
            $words = collect($result['words'])->pluck('word')->join('、');

            throw BusinessException::make("内容包含敏感词（{$words}），发布失败", 'SENSITIVE_WORD_REJECT', 400);
        }

        // 如果动作是审核，抛出异常（可选：或标记为待审核状态）
        if ($result['action'] === 'review') {
            $words = collect($result['words'])->pluck('word')->join('、');

            throw BusinessException::make("内容包含敏感词（{$words}），需要人工审核", 'SENSITIVE_WORD_REVIEW', 400);
        }

        // 返回过滤后的内容
        return $result['filtered'];
    }

    /**
     * 检查是否包含敏感词（不抛出异常，只返回结果）.
     */
    protected function checkSensitiveWords(string $content, string $contentType = 'post'): array
    {
        $service = app(SensitiveWordService::class);

        return $service->filter($content, $contentType, null, \Auth::id());
    }
}
