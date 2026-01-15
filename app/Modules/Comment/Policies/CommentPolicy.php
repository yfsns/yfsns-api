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

namespace App\Modules\Comment\Policies;

use App\Modules\Comment\Models\Comment;
use App\Modules\User\Models\User;

class CommentPolicy
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
     * 查看评论列表（所有人可以查看，包括未登录用户）
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * 查看评论详情
     * 注意：允许未登录用户查看（$user 可以为 null）
     */
    public function view(?User $user, Comment $comment): bool
    {
        // 如果评论状态正常，则可以查看
        return $comment->isPublished();
    }

    /**
     * 创建评论
     * 只有活跃用户可以创建评论
     */
    public function create(User $user): bool
    {
        return $user->isEnabled();
    }

    /**
     * 更新评论
     * 只有作者可以更新自己的评论
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    /**
     * 删除评论
     * 作者可以删除自己的评论，管理员可以删除任何评论
     */
    public function delete(User $user, Comment $comment): bool
    {
        // 作者可以删除自己的评论
        if ($user->id === $comment->user_id) {
            return true;
        }

        // 管理员可以删除任何评论（已在 before 方法中处理，这里保留作为备用）
        return $user->isAdmin();
    }

    /**
     * 点赞评论
     * 活跃用户可以点赞，作者不能给自己点赞
     */
    public function like(User $user, Comment $comment): bool
    {
        // 必须是活跃用户
        if (!$user->isEnabled()) {
            return false;
        }

        // 作者不能给自己点赞
        if ($user->id === $comment->user_id) {
            return false;
        }

        return true;
    }

    /**
     * 回复评论
     * 活跃用户可以回复评论
     */
    public function reply(User $user, Comment $comment): bool
    {
        return $user->isEnabled();
    }

    /**
     * 举报评论
     * 活跃用户可以举报评论
     */
    public function report(User $user, Comment $comment): bool
    {
        return $user->isEnabled();
    }

    /**
     * 审核评论（管理员专用）
     */
    public function review(User $user, Comment $comment): bool
    {
        return $user->isAdmin();
    }

    /**
     * 恢复软删除的评论（管理员专用）
     */
    public function restore(User $user, Comment $comment): bool
    {
        return $user->isAdmin();
    }

    /**
     * 强制删除评论（管理员专用）
     */
    public function forceDelete(User $user, Comment $comment): bool
    {
        return $user->isAdmin();
    }
}
