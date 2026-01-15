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

namespace App\Modules\Like\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseLikeRequest extends FormRequest
{
    /**
     * 准备验证数据，标准化 model_type 值（将驼峰转换为下划线）.
     */
    protected function prepareForValidation(): void
    {
        // 转换驼峰格式字段为下划线格式
        $this->merge([
            'model_type' => $this->modelType ?? $this->model_type,
            'type' => $this->type ?? $this->type, // type 字段支持驼峰和下划线两种格式
        ]);
        
        // 标准化 model_type 值
        if ($this->has('model_type')) {
            $normalized = $this->normalizeModelType($this->input('model_type'));
            $this->merge(['model_type' => $normalized]);
        }
    }

    /**
     * 标准化模型类型值（将驼峰命名转换为下划线命名）.
     */
    private function normalizeModelType(string $modelType): string
    {
        // 如果已经是下划线格式，直接返回
        if (strpos($modelType, '_') !== false) {
            return $modelType;
        }

        // 特殊处理：forumThreadReply -> forum_threadreply（注意：不是 forum_thread_reply）
        if (strtolower($modelType) === 'forumthreadreply') {
            return 'forum_threadreply';
        }

        // 将驼峰命名转换为下划线命名
        // 例如: forumThread -> forum_thread
        $normalized = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $modelType));
        
        // 修正 forum_thread_reply 为 forum_threadreply
        return str_replace('forum_thread_reply', 'forum_threadreply', $normalized);
    }
}

