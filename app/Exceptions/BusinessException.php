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

namespace App\Exceptions;

class BusinessException extends BaseException
{
    /**
     * 创建业务异常.
     */
    public static function make(string $message, string $errorCode = 'BUSINESS_ERROR', int $statusCode = 400, ?array $data = null): self
    {
        $exception = new static($message);
        $exception->errorCode = $errorCode;
        $exception->statusCode = $statusCode;
        $exception->data = $data;

        return $exception;
    }
}
