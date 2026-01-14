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

namespace App\Modules\Wallet\Exceptions;

use Exception;

/**
 * 音符币不足异常.
 */
class InsufficientCoinsException extends Exception
{
    /**
     * 构造函数.
     *
     * @param string         $message  错误消息
     * @param int            $code     错误代码
     * @param null|Exception $previous 前一个异常
     */
    public function __construct(string $message = '音符币余额不足', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 渲染异常.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'error' => 'insufficient_coins',
            'message' => $this->getMessage(),
            'code' => 400,
        ], 400);
    }
}
