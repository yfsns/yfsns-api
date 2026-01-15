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

namespace App\Modules\User\Services;

use App\Modules\File\Services\LocalStorageService;
use App\Modules\User\Models\User;
use App\Modules\User\Models\UserAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function in_array;

/**
 * 头像上传服务 (基础服务)
 *
 * 提供基础的文件上传功能。大部分头像相关功能已迁移到 AvatarReviewService。
 *
 * 主要功能：
 * - 基础文件上传到存储
 * - 兼容性方法（已废弃，建议使用 AvatarReviewService）
 *
 * @deprecated 大部分功能已迁移到 AvatarReviewService，请优先使用 AvatarReviewService
 */
class AvatarService
{
    // 头像配置 - 从配置文件读取
    public const MAX_SIZE = 2 * 1024 * 1024;  // 2MB（向后兼容）

    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public const BASE_PATH = 'uploads';      // 根目录

    public const MODULE_PATH = 'avatars';    // 模块目录

    protected $localStorageService;

    public function __construct(
        LocalStorageService $localStorageService
    ) {
        $this->localStorageService = $localStorageService;
    }

    /**
     * 上传用户头像（审核流程）.
     *
     * @deprecated 此方法已迁移到 AvatarReviewService，请使用 AvatarReviewService::uploadAvatarWithReview()
     *
     * @param UploadedFile $file 上传的文件（已在Request中验证）
     * @param User         $user 用户模型
     *
     * @return array 上传结果
     */
    public function uploadAvatarWithReview(UploadedFile $file, User $user): array
    {
        // 委托给 AvatarReviewService 处理
        $avatarReviewService = app(\App\Modules\User\Services\AvatarReviewService::class);
        return $avatarReviewService->uploadAvatarWithReview($file, $user);
    }

    /**
     * 审核通过头像，同步更新user表.
     *
     * @deprecated 此方法已迁移到 AvatarReviewService，请使用 AvatarReviewService::approveAvatar()
     *
     * @param UserAsset $asset 审核通过的头像资产
     */
    public function approveAvatar(UserAsset $asset): void
    {
        // 委托给 AvatarReviewService 处理
        $avatarReviewService = app(\App\Modules\User\Services\AvatarReviewService::class);
        $avatarReviewService->approveAvatar($asset);
    }

    /**
     * 上传用户头像（直接存储路径到user表）.
     *
     * @deprecated 请使用 uploadAvatarWithReview 方法，支持审核流程.
     *
     * @param UploadedFile $file    上传的文件（已在Request中验证）
     * @param User         $user    用户模型
     * @param array        $options 额外选项
     *
     * @return array 上传结果
     */
    public function uploadAvatar(UploadedFile $file, User $user, array $options = []): array
    {

        // 2. 上传新头像文件
        $result = $this->uploadToStorage($file, $user);

        // 3. 更新用户头像路径
        $user->update([
            'avatar' => $result['path'],
        ]);

        // 4. 头像上传成功

        return [
            'success' => true,
            'path' => $result['path'],
            'url' => $result['url'],
            'avatar_url' => $user->fresh()->avatar_url,
        ];
    }

    /**
     * 上传头像到存储.
     *
     * @param UploadedFile $file 上传的文件
     * @param User         $user 用户模型
     *
     * @return array 上传结果，包含 path 和 url
     */
    protected function uploadToStorage(UploadedFile $file, User $user): array
    {
        // 使用LocalStorageService上传头像
        $fileRecord = $this->localStorageService->upload($file, [
            'module' => 'user',
            'type' => 'avatar',
            'user_id' => $user->id,
        ]);

        // 获取File模型记录
        $fileModel = \App\Modules\File\Models\File::find($fileRecord['id']);

        return [
            'path' => $fileModel->path,
            'url' => $fileRecord['url'],
        ];
    }






    /**
     * 获取用户头像信息.
     *
     * @deprecated 此方法已迁移到 AvatarReviewService，请使用 AvatarReviewService::getUserAvatarReviewStatus()
     *
     * @param User $user 用户模型
     *
     * @return array 头像信息
     */
    public function getAvatarInfo(User $user): array
    {
        // 委托给 AvatarReviewService 处理
        $avatarReviewService = app(\App\Modules\User\Services\AvatarReviewService::class);
        return $avatarReviewService->getUserAvatarReviewStatus($user);
    }




}
