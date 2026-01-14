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

use App\Modules\User\Models\User;
use App\Modules\User\Models\UserAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * 头像审核服务
 * 专门处理用户头像的审核流程，包括上传、AI审核、人工审核等.
 */
class AvatarReviewService
{
    /**
     * 用户每周最多被拒绝次数.
     */
    public const MAX_WEEKLY_REJECTIONS = 3;

    /**
     * 用户上传头像（审核流程）.
     * 
     * 规则：如果用户有待审核的头像，不允许再次上传，必须等待审核通过后才能再次上传.
     */
    public function uploadAvatarWithReview(UploadedFile $file, User $user): array
    {
        // 1. 检查用户是否有待审核的头像
        $pendingAsset = $user->assets()
            ->where('type', UserAsset::TYPE_AVATAR)
            ->where('review_status', UserAsset::REVIEW_PENDING)
            ->latest()
            ->first();

        if ($pendingAsset) {
            throw new Exception('您有待审核的头像，请等待审核通过后再上传新头像');
        }

        // 2. 检查用户是否可以上传头像（检查拒绝次数限制）
        $rejectedCount = $user->assets()
            ->where('type', UserAsset::TYPE_AVATAR)
            ->where('review_status', UserAsset::REVIEW_REJECTED)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($rejectedCount >= self::MAX_WEEKLY_REJECTIONS) {
            throw new Exception('您本周上传头像被拒绝次数过多，请稍后再试或联系客服');
        }

        // 3. 验证文件
        $this->validateAvatar($file);

        // 4. 上传文件到临时存储
        $uploadedFile = $this->uploadToTempStorage($file, $user);

        // 5. 创建新的待审核记录
            $asset = $this->createPendingAsset($uploadedFile, $user);

        // 6. 更新用户审核状态
        $this->updateUserReviewStatus($user, $asset);

        // 7. 提交审核
        $this->submitForReview($asset, $user);

        return [
            'asset_id' => $asset->id,
            'status' => 'pending',
            'message' => '头像已上传，等待审核通过后展示',
        ];
    }

    /**
     * 验证头像文件.
     */
    protected function validateAvatar(UploadedFile $file): void
    {
        // 检查文件大小（2MB）
        if ($file->getSize() > 2 * 1024 * 1024) {
            throw new Exception('头像文件大小不能超过 2MB');
        }

        // 检查 MIME 类型
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new Exception('头像文件格式不支持，仅支持: jpg, png, gif, webp');
        }

        // 检查文件扩展名
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('头像文件扩展名不支持');
        }
    }

    /**
     * 上传到临时存储.
     * 
     * 注意：LocalStorageService::upload() 返回驼峰格式，需要转换为下划线格式供 createPendingAsset 使用.
     */
    protected function uploadToTempStorage(UploadedFile $file, User $user): array
    {
        $localStorageService = app(\App\Modules\File\Services\LocalStorageService::class);

        // upload() 返回驼峰格式：id, name, originalName, url, size, mimeType, ...
        $uploadResult = $localStorageService->upload($file, [
            'module' => 'user',
            'type' => 'avatar_pending',
            'user_id' => $user->id,
        ]);

        // 获取文件记录以获取 path
        $fileModel = \App\Modules\File\Models\File::find($uploadResult['id']);

        // 获取图片尺寸（如果可能）
        $width = null;
        $height = null;
        if (str_starts_with($uploadResult['mimeType'], 'image/')) {
            try {
                $imageInfo = getimagesize($file->getRealPath());
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                }
            } catch (Exception $e) {
                // 如果获取尺寸失败，忽略错误
            }
        }

        // 转换为下划线格式供 createPendingAsset 使用
        return [
            'path' => $fileModel->path,
            'url' => $uploadResult['url'],
            'mime_type' => $uploadResult['mimeType'], // 驼峰转下划线
            'size' => $uploadResult['size'],
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * 创建待审核的头像资产.
     */
    protected function createPendingAsset(array $uploadedFile, User $user): UserAsset
    {
        return UserAsset::create([
            'user_id' => $user->id,
            'type' => UserAsset::TYPE_AVATAR,
            'name' => 'pending_avatar_' . time(),
            'path' => $uploadedFile['path'],
            'url' => $uploadedFile['url'],
            'mime_type' => $uploadedFile['mime_type'],
            'size' => $uploadedFile['size'],
            'width' => $uploadedFile['width'] ?? null,
            'height' => $uploadedFile['height'] ?? null,
            'review_status' => UserAsset::REVIEW_PENDING,
            'status' => 1, // 正常状态
        ]);
    }

    /**
     * 更新待审核的头像资产.
     * 
     * 当用户再次上传头像时，如果存在待审核的头像记录，则更新该记录而不是创建新记录.
     */
    protected function updatePendingAsset(UserAsset $asset, array $uploadedFile): UserAsset
    {
        // 删除旧文件
        if ($asset->path && Storage::exists($asset->path)) {
            Storage::delete($asset->path);
        }

        // 清除旧的 AI 审核结果（因为这是新的头像，需要重新审核）
        $extra = $asset->extra ?? [];
        unset($extra['ai_review_result'], $extra['queued_at']);

        // 更新资产信息
        $asset->update([
            'name' => 'pending_avatar_' . time(),
            'path' => $uploadedFile['path'],
            'url' => $uploadedFile['url'],
            'mime_type' => $uploadedFile['mime_type'],
            'size' => $uploadedFile['size'],
            'width' => $uploadedFile['width'] ?? null,
            'height' => $uploadedFile['height'] ?? null,
            'review_status' => UserAsset::REVIEW_PENDING, // 确保状态为待审核
            'review_remark' => null, // 清除之前的审核备注
            'reviewer_id' => null, // 清除审核员
            'reviewed_at' => null, // 清除审核时间
            'extra' => $extra, // 清除 AI 审核结果
            'status' => 1, // 确保状态正常
        ]);

        return $asset;
    }

    /**
     * 更新用户审核状态.
     */
    protected function updateUserReviewStatus(User $user, UserAsset $asset): void
    {
        $user->update([
            'avatar_review_status' => 'pending',
            'pending_avatar' => $asset->path,
            'avatar_review_submitted_at' => now(),
        ]);
    }

    /**
     * 提交审核.
     */
    protected function submitForReview(UserAsset $asset, User $user): void
    {
        // AI预审核
        $aiResult = $this->performAIReview($asset);

        if ($aiResult['risk_level'] === 'safe') {
            // AI判断安全，直接通过
            $this->approveAvatar($asset, null, 'AI审核通过');
        } elseif ($aiResult['risk_level'] === 'dangerous') {
            // AI判断危险，直接拒绝
            $this->rejectAvatar($asset, null, 'AI检测到违规内容');
        } else {
            // 需要人工审核
            $this->queueForManualReview($asset, $aiResult);
        }
    }

    /**
     * 执行AI审核.
     */
    protected function performAIReview(UserAsset $asset): array
    {
        // TODO: 集成AI审核服务
        // 这里可以调用第三方AI审核API，如腾讯云内容安全、阿里云内容安全等

        // 暂时模拟AI审核结果
        return [
            'risk_level' => 'unknown', // safe, dangerous, unknown
            'confidence' => 0.85,
            'details' => [],
        ];
    }

    /**
     * 排队等待人工审核.
     */
    protected function queueForManualReview(UserAsset $asset, array $aiResult): void
    {
        // 更新资产的AI审核结果
        $asset->update([
            'extra' => array_merge($asset->extra ?? [], [
                'ai_review_result' => $aiResult,
                'queued_at' => now(),
            ]),
        ]);

        // TODO: 可以在这里加入消息队列，通知审核员
        Log::info('头像进入人工审核队列', [
            'asset_id' => $asset->id,
            'user_id' => $asset->user_id,
        ]);
    }

    /**
     * 审核通过头像.
     */
    public function approveAvatar(UserAsset $asset, ?User $reviewer = null, string $remark = ''): void
    {
        DB::transaction(function () use ($asset, $reviewer, $remark) {
            // 1. 更新资产审核状态
            $asset->update([
                'review_status' => UserAsset::REVIEW_APPROVED,
                'reviewer_id' => $reviewer?->id,
                'review_remark' => $remark,
                'reviewed_at' => now(),
            ]);

            // 2. 删除用户当前头像文件（如果存在且不同）
            if ($asset->user->avatar && $asset->user->avatar !== $asset->path) {
                Storage::delete($asset->user->avatar);
            }

            // 3. 更新用户头像
            $asset->user->update([
                'avatar' => $asset->path,
                'avatar_review_status' => 'approved',
                'pending_avatar' => null,
                'avatar_review_submitted_at' => null,
            ]);

            // 4. 清理其他待审核头像
            $this->cleanupPendingAvatars($asset->user, $asset);

            // 5. 发送通知
            $this->notifyUser($asset->user, 'approved', $remark);

            Log::info('头像审核通过', [
                'asset_id' => $asset->id,
                'user_id' => $asset->user_id,
                'reviewer_id' => $reviewer?->id,
            ]);
        });
    }

    /**
     * 拒绝头像.
     */
    public function rejectAvatar(UserAsset $asset, ?User $reviewer = null, string $remark = ''): void
    {
        DB::transaction(function () use ($asset, $reviewer, $remark) {
            // 1. 更新资产审核状态
            $asset->update([
                'review_status' => UserAsset::REVIEW_REJECTED,
                'reviewer_id' => $reviewer?->id,
                'review_remark' => $remark,
                'reviewed_at' => now(),
                'status' => 0, // 禁用状态
            ]);

            // 2. 删除待审核头像文件
            Storage::delete($asset->path);

            // 3. 重置用户审核状态
            $asset->user->update([
                'avatar_review_status' => $asset->user->avatar ? 'approved' : 'not_submitted',
                'pending_avatar' => null,
                'avatar_review_submitted_at' => null,
            ]);

            // 4. 发送通知
            $this->notifyUser($asset->user, 'rejected', $remark);

            Log::info('头像审核拒绝', [
                'asset_id' => $asset->id,
                'user_id' => $asset->user_id,
                'reviewer_id' => $reviewer?->id,
                'reason' => $remark,
            ]);
        });
    }

    /**
     * 获取用户当前有效的头像.
     */
    public function getUserEffectiveAvatar(User $user): ?string
    {
        // 如果有审核通过的头像，返回当前头像
        if ($user->avatar) {
            return $user->avatar;
        }

        // 如果没有审核通过的头像，返回默认头像
        return $this->getDefaultAvatar();
    }

    /**
     * 获取用户头像审核状态.
     */
    public function getUserAvatarReviewStatus(User $user): array
    {
        $pendingAsset = $user->assets()
            ->where('type', UserAsset::TYPE_AVATAR)
            ->where('review_status', UserAsset::REVIEW_PENDING)
            ->latest()
            ->first();

        // 计算用户的头像审核状态
        $reviewStatus = 'not_submitted'; // 默认：未提交
        if ($user->avatar) {
            $reviewStatus = 'approved'; // 有头像：已审核通过
        } elseif ($pendingAsset) {
            $reviewStatus = 'pending'; // 有待审核头像：审核中
        }

        return [
            'current_avatar' => $this->getUserEffectiveAvatar($user),
            'review_status' => $reviewStatus,
            'pending_asset' => $pendingAsset ? [
                'id' => $pendingAsset->id,
                'url' => $pendingAsset->getFullUrl(),
                'submitted_at' => $pendingAsset->created_at,
            ] : null,
            'can_upload' => $this->canUserUploadAvatar($user),
        ];
    }

    /**
     * 检查用户是否可以上传头像.
     * 
     * 规则：如果用户有待审核的头像，不允许再次上传，必须等待审核通过后才能再次上传.
     */
    protected function canUserUploadAvatar(User $user): bool
    {
        // 检查用户是否有待审核的头像
        $pendingAsset = $user->assets()
            ->where('type', UserAsset::TYPE_AVATAR)
            ->where('review_status', UserAsset::REVIEW_PENDING)
            ->exists();

        if ($pendingAsset) {
            return false;
        }

        // 检查用户是否有过多审核失败记录
        $rejectedCount = $user->assets()
            ->where('type', UserAsset::TYPE_AVATAR)
            ->where('review_status', UserAsset::REVIEW_REJECTED)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return $rejectedCount < self::MAX_WEEKLY_REJECTIONS;
    }

    /**
     * 获取默认头像.
     */
    protected function getDefaultAvatar(): ?string
    {
        // TODO: 返回默认头像路径
        return null;
    }

    /**
     * 清理其他待审核头像.
     */
    protected function cleanupPendingAvatars(User $user, UserAsset $approvedAsset): void
    {
        $user->assets()
            ->where('type', UserAsset::TYPE_AVATAR)
            ->where('review_status', UserAsset::REVIEW_PENDING)
            ->where('id', '!=', $approvedAsset->id)
            ->each(function ($asset) {
                Storage::delete($asset->path);
                $asset->delete();
            });
    }

    /**
     * 发送用户通知.
     */
    protected function notifyUser(User $user, string $action, string $remark = ''): void
    {
        // TODO: 发送通知给用户
        // 这里可以发送站内信、邮件、短信等通知

        $message = match ($action) {
            'approved' => '您的头像审核已通过，现在可以正常显示了。',
            'rejected' => '您的头像审核未通过：' . $remark,
            default => '头像审核状态已更新。',
        };

        Log::info('头像审核通知', [
            'user_id' => $user->id,
            'action' => $action,
            'message' => $message,
        ]);
    }

    /**
     * 获取待审核头像列表（管理员用）.
     */
    public function getPendingReviews(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = UserAsset::with(['user', 'reviewer'])
            ->where('type', UserAsset::TYPE_AVATAR)
            ->where('review_status', UserAsset::REVIEW_PENDING)
            ->orderBy('created_at', 'desc');

        // 应用过滤器
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }
}
