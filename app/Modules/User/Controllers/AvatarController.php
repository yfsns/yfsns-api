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

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Requests\UploadAvatarRequest;
use App\Modules\User\Resources\AvatarResource;
use App\Modules\User\Resources\AvatarStatusResource;
use App\Modules\User\Resources\AvatarUploadResource;
use App\Modules\User\Services\AvatarReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 用户头像控制器
 * 处理用户头像上传、审核状态查询等功能.
 * 采用声明式开发风格，提供清晰的API接口。
 */
class AvatarController extends Controller
{
    protected AvatarReviewService $avatarService;

    public function __construct(AvatarReviewService $avatarService)
    {
        $this->avatarService = $avatarService;
    }

    /**
     * 上传用户头像.
     * POST /api/v1/users/avatar
     */
    public function upload(UploadAvatarRequest $request): JsonResponse
    {
        $user = auth()->user();

        // 使用 AvatarReviewService 确保检查待审核头像
        $result = $this->avatarService->uploadAvatarWithReview(
            $request->file('avatar'),
            $user
        );

        // 获取刚创建的 asset 对象，用于获取 URL
        $asset = \App\Modules\User\Models\UserAsset::find($result['asset_id']);

        // 获取完整的审核状态信息
        $statusInfo = $this->avatarService->getUserAvatarReviewStatus($user);

        // 准备 Resource 数据（下划线格式，Resource 会转换为驼峰格式）
        $resourceData = array_merge($result, [
            'can_upload' => $statusInfo['can_upload'],
            'review_status' => $statusInfo['review_status'],
            'pending_avatar_url' => $asset ? $asset->getFullUrl() : null,
        ]);

        return response()->success(new AvatarUploadResource($resourceData), '上传头像成功');
    }

    /**
     * 获取用户头像信息.
     * GET /api/user/avatar/info
     */
    public function info(): JsonResponse
    {
        $user = auth()->user();
        $info = $this->avatarService->getUserAvatarReviewStatus($user);

        return response()->success(new AvatarResource($info), '获取头像信息成功');
    }

    /**
     * 获取用户头像审核状态.
     * GET /api/v1/users/avatar/status
     */
    public function status(): JsonResponse
    {
        $user = auth()->user();
        $status = $this->avatarService->getUserAvatarReviewStatus($user);

        return response()->success(new AvatarStatusResource($status), '获取头像状态成功');
    }



}
