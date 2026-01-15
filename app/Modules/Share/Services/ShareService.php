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

namespace App\Modules\Share\Services;

use App\Modules\Share\Models\Share;
use App\Modules\User\Services\UserService;

use function get_class;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ShareService
{
    /**
     * 构造函数.
     */
    public function __construct(
        private UserService $userService
    ) {
    }

    /**
     * 分享内容.
     *
     * @param Model   $model    要分享的模型实例
     * @param string  $platform 分享平台
     * @param string  $type     分享类型
     * @param Request $request  请求实例
     */
    public function share(Model $model, string $platform = 'default', string $type = 'default', ?Request $request = null): array
    {
        $userId = $this->userService->getCurrentUserId();

        // 创建分享记录
        $share = Share::create([
            'user_id' => $userId,
            'shareable_id' => $model->id,
            'shareable_type' => get_class($model),
            'type' => $type,
            'platform' => $platform,
            'url' => $model->getShareUrl($platform),
            'ip' => $request?->ip(),
            'device' => $request?->userAgent(),
        ]);

        // 如果模型有分享计数，则增加计数
        if (method_exists($model, 'incrementShareCount')) {
            $model->incrementShareCount();
        }

        return [
            'message' => '分享成功',
            'share' => $share,
        ];
    }

    /**
     * 获取用户的分享列表.
     *
     * @param null|string $type          分享类型
     * @param null|string $shareableType 被分享内容的类型
     * @param int         $page          页码
     * @param int         $perPage       每页数量
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserShares(?string $type = null, ?string $shareableType = null, int $page = 1, int $perPage = 10)
    {
        $query = Share::where('user_id', $this->userService->getCurrentUserId())
            ->with(['shareable', 'user:id,nickname']);

        if ($type) {
            $query->where('type', $type);
        }

        if ($shareableType) {
            $query->where('shareable_type', $shareableType);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * 获取内容的分享数量.
     *
     * @param Model $model 要统计的模型实例
     */
    public function getShareCount(Model $model): int
    {
        return Share::where('shareable_id', $model->id)
            ->where('shareable_type', get_class($model))
            ->count();
    }

    /**
     * 获取内容的分享链接.
     *
     * @param Model  $model    要获取链接的模型实例
     * @param string $platform 分享平台
     */
    public function getShareUrl(Model $model, string $platform = 'default'): string
    {
        return $model->getShareUrl($platform);
    }

    /**
     * 取消分享.
     *
     * @param Model $model 要取消分享的模型实例
     */
    public function unshare(Model $model): bool
    {
        $userId = $this->userService->getCurrentUserId();

        $share = Share::where('user_id', $userId)
            ->where('shareable_id', $model->id)
            ->where('shareable_type', get_class($model))
            ->first();

        if (! $share) {
            return false;
        }

        // 如果模型有分享计数，则减少计数
        if (method_exists($model, 'decrementShareCount')) {
            $model->decrementShareCount();
        }

        return $share->delete();
    }

    /**
     * 检查是否已分享.
     *
     * @param Model $model 要检查的模型实例
     */
    public function check(Model $model): bool
    {
        $userId = $this->userService->getCurrentUserId();

        return Share::where('user_id', $userId)
            ->where('shareable_id', $model->id)
            ->where('shareable_type', get_class($model))
            ->exists();
    }
}
