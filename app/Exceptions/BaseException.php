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

use Exception;

class BaseException extends Exception
{
    /**
     * HTTP 状态码
     */
    protected int $statusCode = 500;

    /**
     * 错误代码
     */
    protected string $errorCode = 'UNKNOWN_ERROR';

    /**
     * 错误数据.
     */
    protected ?array $data = null;

    /**
     * 获取 HTTP 状态码
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * 获取错误代码
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * 获取错误数据.
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * 创建异常实例.
     *
     * @param string     $message    错误消息
     * @param string     $errorCode  错误代码
     * @param int        $statusCode HTTP 状态码
     * @param null|array $data       错误数据
     *
     * @return static
     */
    public static function make(string $message, string $errorCode = 'UNKNOWN_ERROR', int $statusCode = 500, ?array $data = null): self
    {
        $exception = new static($message);
        $exception->errorCode = $errorCode;
        $exception->statusCode = $statusCode;
        $exception->data = $data;

        return $exception;
    }
}
