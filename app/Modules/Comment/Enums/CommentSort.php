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

namespace App\Modules\Comment\Enums;

enum CommentSort: string
{
    case LATEST = 'latest';
    case HOT = 'hot';

    /**
     * 获取默认排序
     */
    public static function default(): self
    {
        return self::LATEST;
    }

    /**
     * 从字符串创建枚举
     */
    public static function fromString(string $sort): self
    {
        return self::tryFrom($sort) ?? self::default();
    }
}

