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
use App\Modules\User\Models\UserMention;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class UserMentionService
{
    /**
     * 创建@关系
     *
     * @param int $senderId 发送者ID
     * @param array $receiverIds 接收者ID数组
     * @param string $contentType 内容类型
     * @param int $contentId 内容ID
     * @return int 创建的@记录数量
     */
    public function createMentions(int $senderId, array $receiverIds, string $contentType, int $contentId): int
    {
        if (empty($receiverIds)) {
            return 0;
        }

        // 过滤掉自己
        $receiverIds = array_filter($receiverIds, fn($id) => $id != $senderId);

        if (empty($receiverIds)) {
            return 0;
        }

        // 批量获取用户信息
        $users = User::whereIn('id', array_merge([$senderId], $receiverIds))
            ->select('id', 'username', 'nickname')
            ->get()
            ->keyBy('id');

        $sender = $users->get($senderId);
        if (!$sender) {
            throw new Exception('发送者不存在');
        }

        $mentions = [];
        foreach ($receiverIds as $position => $receiverId) {
            $receiver = $users->get($receiverId);
            if ($receiver) {
                $mentions[] = [
                    'sender_id' => $senderId,
                    'receiver_id' => $receiverId,
                    'content_type' => $contentType,
                    'content_id' => $contentId,
                    'username' => $receiver->username,
                    'nickname_at_time' => $receiver->nickname ?: $receiver->username,
                    'position' => $position,
                    'status' => UserMention::STATUS_UNREAD,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (empty($mentions)) {
            return 0;
        }

        // 插入数据库
        $inserted = DB::table('user_mentions')->insert($mentions);

        // 触发@通知事件
        $this->triggerMentionEvents($sender, $users, $mentions, $contentType, $contentId);

        return $inserted;
    }

    /**
     * 触发@通知事件
     */
    protected function triggerMentionEvents(User $sender, $users, array $mentions, string $contentType, int $contentId): void
    {
        try {
            // 获取内容对象（动态或评论）
            $content = null;
            if ($contentType === 'post') {
                $content = \App\Modules\Post\Models\Post::find($contentId);
            } elseif ($contentType === 'comment') {
                $content = \App\Modules\Comment\Models\Comment::find($contentId);
            }

            if (!$content) {
                return;
            }

            // 为每个@的用户触发事件
            foreach ($mentions as $mention) {
                $receiver = $users->get($mention['receiver_id']);
                if ($receiver && $sender->id !== $receiver->id) {
                    event(new \App\Modules\Notification\Events\UserMentioned(
                        $sender,
                        $receiver,
                        $content
                    ));
                }
            }
        } catch (\Exception $e) {
            // 记录错误但不影响@记录创建
            \Log::error('@通知发送失败', [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取用户的@记录列表
     *
     * @param int $userId 用户ID
     * @param int $perPage 每页数量
     * @param string|null $contentType 内容类型筛选
     * @param string|null $status 状态筛选
     * @return LengthAwarePaginator
     */
    public function getUserMentions(int $userId, int $perPage = 20, ?string $contentType = null, ?string $status = null): LengthAwarePaginator
    {
        $query = UserMention::with([
            'sender' => function ($q) {
                $q->select('id', 'username', 'nickname', 'avatar');
            }
        ])
        ->byReceiver($userId)
        ->latest();

        if ($contentType) {
            $query->byContentType($contentType);
        }

        if ($status) {
            if ($status === UserMention::STATUS_UNREAD) {
                $query->unread();
            } elseif ($status === UserMention::STATUS_READ) {
                $query->read();
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * 获取用户被@的动态列表（兼容原有接口）
     *
     * @param int $userId 用户ID
     * @param int $perPage 每页数量
     * @return LengthAwarePaginator
     */
    public function getUserMentionedPosts(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        // 从user_mentions表中获取被@的动态
        $mentions = UserMention::with([
            'sender' => function ($q) {
                $q->select('id', 'username', 'nickname', 'avatar');
            }
        ])
        ->byReceiver($userId)
        ->byContentType(UserMention::TYPE_POST)
        ->latest()
        ->paginate($perPage);

        // 转换为原有接口格式
        $mentions->getCollection()->transform(function ($mention) {
            // 这里需要根据content_type和content_id获取对应的内容
            // 暂时返回mention对象，具体内容获取逻辑需要根据实际需求实现
            $mention->user = $mention->sender;
            $mention->mentioned_as = $mention->nickname_at_time;
            return $mention;
        });

        return $mentions;
    }

    /**
     * 获取用户的@统计信息
     *
     * @param int $userId 用户ID
     * @return array
     */
    public function getUserMentionStats(int $userId): array
    {
        $stats = UserMention::byReceiver($userId)
            ->selectRaw('
                COUNT(*) as total_mentions,
                COUNT(CASE WHEN status = ? THEN 1 END) as unread_count,
                COUNT(CASE WHEN created_at >= ? THEN 1 END) as recent_mentions
            ', [
                UserMention::STATUS_UNREAD,
                now()->subDays(7)
            ])
            ->first();

        return [
            'total_mentions' => (int) $stats->total_mentions,
            'unread_count' => (int) $stats->unread_count,
            'recent_mentions' => (int) $stats->recent_mentions,
        ];
    }

    /**
     * 标记@记录为已读
     *
     * @param int $mentionId @记录ID
     * @param int $userId 用户ID（验证权限）
     * @return bool
     */
    public function markAsRead(int $mentionId, int $userId): bool
    {
        $mention = UserMention::byReceiver($userId)->find($mentionId);

        if (!$mention) {
            return false;
        }

        return $mention->markAsRead();
    }

    /**
     * 批量标记@记录为已读
     *
     * @param array $mentionIds @记录ID数组
     * @param int $userId 用户ID（验证权限）
     * @return int 成功标记的数量
     */
    public function markAsReadBulk(array $mentionIds, int $userId): int
    {
        return UserMention::markAsReadBulk($mentionIds, $userId);
    }

    /**
     * 标记所有@记录为已读
     *
     * @param int $userId 用户ID
     * @param string|null $contentType 内容类型筛选
     * @return int 成功标记的数量
     */
    public function markAllAsRead(int $userId, ?string $contentType = null): int
    {
        $query = UserMention::byReceiver($userId)->unread();

        if ($contentType) {
            $query->byContentType($contentType);
        }

        return $query->update([
            'status' => UserMention::STATUS_READ,
            'read_at' => now(),
        ]);
    }

    /**
     * 删除@记录
     *
     * @param int $mentionId @记录ID
     * @param int $userId 用户ID（验证权限）
     * @return bool
     */
    public function deleteMention(int $mentionId, int $userId): bool
    {
        $mention = UserMention::byReceiver($userId)->find($mentionId);

        if (!$mention) {
            return false;
        }

        return $mention->delete();
    }

    /**
     * 批量删除@记录
     *
     * @param array $mentionIds @记录ID数组
     * @param int $userId 用户ID（验证权限）
     * @return int 删除的数量
     */
    public function deleteMentionsBulk(array $mentionIds, int $userId): int
    {
        return UserMention::byReceiver($userId)
            ->whereIn('id', $mentionIds)
            ->delete();
    }

    /**
     * 删除指定内容的@记录
     *
     * @param string $contentType 内容类型
     * @param int $contentId 内容ID
     * @return int 删除的数量
     */
    public function deleteMentionsByContent(string $contentType, int $contentId): int
    {
        return UserMention::byContentType($contentType)
            ->byContentId($contentId)
            ->delete();
    }

    /**
     * 获取未读@数量
     *
     * @param int $userId 用户ID
     * @param string|null $contentType 内容类型筛选
     * @return int
     */
    public function getUnreadCount(int $userId, ?string $contentType = null): int
    {
        $query = UserMention::byReceiver($userId)->unread();

        if ($contentType) {
            $query->byContentType($contentType);
        }

        return $query->count();
    }

    /**
     * 检查用户是否被@过
     *
     * @param int $userId 用户ID
     * @param string $contentType 内容类型
     * @param int $contentId 内容ID
     * @return bool
     */
    public function isUserMentioned(int $userId, string $contentType, int $contentId): bool
    {
        return UserMention::byReceiver($userId)
            ->byContentType($contentType)
            ->byContentId($contentId)
            ->exists();
    }

    /**
     * 获取内容的@用户列表
     *
     * @param string $contentType 内容类型
     * @param int $contentId 内容ID
     * @return Collection
     */
    public function getContentMentions(string $contentType, int $contentId): Collection
    {
        return UserMention::with([
            'sender' => function ($q) {
                $q->select('id', 'username', 'nickname', 'avatar');
            },
            'receiver' => function ($q) {
                $q->select('id', 'username', 'nickname', 'avatar');
            }
        ])
        ->byContentType($contentType)
        ->byContentId($contentId)
        ->latest()
        ->get();
    }
}
