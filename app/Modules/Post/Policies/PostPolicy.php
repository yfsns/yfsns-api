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

namespace App\Modules\Post\Policies;

use App\Modules\Post\Models\Post;
use App\Modules\User\Models\User;

class PostPolicy
{
    /**
     * 在执行任何授权检查之前执行
     * 如果返回非 null，将直接使用该结果
     */
    public function before(User $user, string $ability): ?bool
    {
        // 超级管理员拥有所有权限
        if ($user->isAdmin()) {
            return true;
        }

        // 返回 null 继续执行其他授权方法
        return null;
    }

    /**
     * 查看动态列表（所有人可以查看，包括未登录用户）
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * 查看动态详情
     * 使用 Post 模型的 canViewBy() 方法判断
     * 注意：允许未登录用户查看（$user 可以为 null）
     */
    public function view(?User $user, Post $post): bool
    {
        // 如果未登录，使用 null
        $userId = $user?->id ?? null;

        return $post->canViewBy($userId);
    }

    /**
     * 创建动态
     * 只有活跃用户可以创建
     */
    public function create(User $user): bool
    {
        return $user->isEnabled();
    }

    /**
     * 更新动态
     * 只有作者可以更新自己的动态
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * 删除动态
     * 作者可以删除自己的动态，管理员可以删除任何动态
     */
    public function delete(User $user, Post $post): bool
    {
        // 作者可以删除自己的动态
        if ($user->id === $post->user_id) {
            return true;
        }

        // 管理员可以删除任何动态（已在 before 方法中处理，这里保留作为备用）
        return $user->isAdmin();
    }

    /**
     * 发布动态
     * 只有作者可以发布自己的动态
     */
    public function publish(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * 撤回动态
     * 只有作者可以撤回自己的动态
     */
    public function unpublish(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * 提交审核
     * 只有作者可以提交自己的动态审核
     */
    public function submitForReview(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * 审核动态（管理员专用）
     */
    public function review(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }

    /**
     * 置顶动态（管理员专用）
     */
    public function pin(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }

    /**
     * 取消置顶动态（管理员专用）
     */
    public function unpin(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }

    /**
     * 点赞动态
     * 活跃用户可以点赞，作者不能给自己点赞
     */
    public function like(User $user, Post $post): bool
    {
        // 必须是活跃用户
        if (!$user->isEnabled()) {
            return false;
        }

        // 作者不能给自己点赞
        if ($user->id === $post->user_id) {
            return false;
        }

        return true;
    }

    /**
     * 收藏动态
     * 活跃用户可以收藏，作者不能收藏自己
     */
    public function collect(User $user, Post $post): bool
    {
        // 必须是活跃用户
        if (!$user->isEnabled()) {
            return false;
        }

        // 作者不能收藏自己
        if ($user->id === $post->user_id) {
            return false;
        }

        return true;
    }

    /**
     * 分享动态
     * 活跃用户可以分享
     */
    public function share(User $user, Post $post): bool
    {
        return $user->isEnabled();
    }

    /**
     * 举报动态
     * 活跃用户可以举报
     */
    public function report(User $user, Post $post): bool
    {
        return $user->isEnabled();
    }

    /**
     * 恢复软删除的动态（管理员专用）
     */
    public function restore(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }

    /**
     * 强制删除动态（管理员专用）
     */
    public function forceDelete(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }
}

