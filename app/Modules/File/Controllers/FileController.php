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

namespace App\Modules\File\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\File\Requests\UploadFileRequest;
use App\Modules\File\Services\LocalStorageService;
use Illuminate\Http\JsonResponse;

/**
 * @group 文件模块
 *
 * @name 文件模块
 */
class FileController extends Controller
{
    protected LocalStorageService $storageService;

    public function __construct(LocalStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * 统一文件上传接口
     *
     * 支持本地文件存储，支持单文件和多文件上传
     * 所有配置通过 config/upload.php 进行管理
     *
     * @bodyParam file file optional 单个文件（与files二选一）
     * @bodyParam files file[] optional 多个文件数组（与file二选一）
     * @bodyParam type string required 文件类型
     * @bodyParam module string optional 所属模块
     * @bodyParam module_id int optional 模块ID
     */
    public function unifiedUpload(UploadFileRequest $request): JsonResponse
    {
        $data = $request->validated();

        // 根据请求数据自动选择上传方式
        if ($request->hasFile('file')) {
            $result = $this->storageService->upload($request->file('file'), $data);
            return response()->json([
                'code' => 200,
                'message' => '上传成功',
                'data' => $result,
            ], 200);
        }

        if ($request->has('files')) {
            $results = $this->storageService->uploadMultiple($request->file('files'), $data);
            return response()->json([
                'code' => 200,
                'message' => '批量上传成功',
                'data' => $results,
            ], 200);
        }

        return response()->json([
            'code' => 400,
            'message' => '未找到上传的文件',
            'data' => null,
        ], 400);
    }

}
