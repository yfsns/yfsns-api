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

namespace App\Modules\Topic\Listeners;

use App\Modules\Topic\Events\TopicsUpdated;
use App\Modules\Topic\Models\Topic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 话题更新事件监听器（通用）
 *
 * 监听TopicsUpdated事件，处理所有内容类型的话题关联业务逻辑
 * 支持：post, comment, article, forum_thread等任何内容类型
 */
class TopicsUpdatedListener
{
    /**
     * 处理话题更新事件
     *
     * @param TopicsUpdated $event 话题更新事件
     */
    public function handle(TopicsUpdated $event): void
    {
        $this->processTopics(
            $event->contentType,
            $event->contentId,
            $event->topicIds,
            $event->action
        );
    }

    /**
     * 处理话题关联的通用方法
     *
     * @param string $type     内容类型：post 或 comment
     * @param int    $contentId 内容ID
     * @param array  $topicIds 话题ID数组
     * @param string $action   操作类型
     */
    protected function processTopics(string $type, int $contentId, array $topicIds, string $action): void
    {
        try {
            match ($action) {
                'sync' => $this->syncTopics($type, $contentId, $topicIds),
                'attach' => $this->attachTopics($type, $contentId, $topicIds),
                'detach' => $this->detachTopics($type, $contentId, $topicIds),
                default => Log::warning('未知的话题操作类型', [
                    'action' => $action,
                    'type' => $type,
                    'content_id' => $contentId,
                ]),
            };
        } catch (\Exception $e) {
            Log::error("处理{$type}话题更新事件失败", [
                'type' => $type,
                'content_id' => $contentId,
                'topic_ids' => $topicIds,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            // 可以选择重新抛出异常或进行其他错误处理
            throw $e;
        }
    }

    /**
     * 同步话题关联
     *
     * @param string $type     内容类型：post 或 comment
     * @param int    $contentId 内容ID
     * @param array  $topicIds 话题ID数组
     */
    protected function syncTopics(string $type, int $contentId, array $topicIds): void
    {
        // 过滤出有效的话题ID
        $validTopicIds = $this->validateTopicIds($topicIds);

        if (empty($validTopicIds)) {
            // 如果没有有效话题，删除所有关联
            DB::table('post_topics')->where($type . '_id', $contentId)->delete();
            return;
        }

        // 删除旧的话题关联
        DB::table('post_topics')->where($type . '_id', $contentId)->delete();

        // 插入新的话题关联
        foreach ($validTopicIds as $index => $topicId) {
            DB::table('post_topics')->insert([
                $type . '_id' => $contentId,
                'topic_id' => $topicId,
                'position' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 更新话题的计数
        $this->updateTopicCounts($validTopicIds);

        Log::info("{$type}话题同步完成", [
            $type . '_id' => $contentId,
            'topic_ids' => $validTopicIds,
        ]);
    }

    /**
     * 添加话题关联
     *
     * @param string $type     内容类型：post 或 comment
     * @param int    $contentId 内容ID
     * @param array  $topicIds 要添加的话题ID数组
     */
    protected function attachTopics(string $type, int $contentId, array $topicIds): void
    {
        $validTopicIds = $this->validateTopicIds($topicIds);

        foreach ($validTopicIds as $topicId) {
            // 检查是否已存在关联
            $exists = DB::table('post_topics')
                ->where($type . '_id', $contentId)
                ->where('topic_id', $topicId)
                ->exists();

            if (!$exists) {
                $maxPosition = DB::table('post_topics')
                    ->where($type . '_id', $contentId)
                    ->max('position') ?? -1;

                DB::table('post_topics')->insert([
                    $type . '_id' => $contentId,
                    'topic_id' => $topicId,
                    'position' => $maxPosition + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 增加话题计数
                Topic::where('id', $topicId)->increment('post_count');
            }
        }

        Log::info("{$type}话题添加完成", [
            $type . '_id' => $contentId,
            'added_topic_ids' => $validTopicIds,
        ]);
    }

    /**
     * 移除话题关联
     *
     * @param string $type     内容类型：post 或 comment
     * @param int    $contentId 内容ID
     * @param array  $topicIds 要移除的话题ID数组
     */
    protected function detachTopics(string $type, int $contentId, array $topicIds): void
    {
        foreach ($topicIds as $topicId) {
            $deleted = DB::table('post_topics')
                ->where($type . '_id', $contentId)
                ->where('topic_id', $topicId)
                ->delete();

            if ($deleted > 0) {
                // 减少话题计数
                Topic::where('id', $topicId)
                    ->where('post_count', '>', 0)
                    ->decrement('post_count');
            }
        }

        Log::info("{$type}话题移除完成", [
            $type . '_id' => $contentId,
            'removed_topic_ids' => $topicIds,
        ]);
    }

    /**
     * 验证话题ID数组，返回有效的活跃话题ID
     *
     * @param array $topicIds 话题ID数组
     *
     * @return array 有效的活跃话题ID数组
     */
    protected function validateTopicIds(array $topicIds): array
    {
        if (empty($topicIds)) {
            return [];
        }

        return Topic::whereIn('id', $topicIds)
            ->where('status', 1) // 只返回活跃话题
            ->pluck('id')
            ->toArray();
    }

    /**
     * 更新话题的计数
     *
     * @param array $topicIds 话题ID数组
     */
    protected function updateTopicCounts(array $topicIds): void
    {
        foreach ($topicIds as $topicId) {
            $actualCount = DB::table('post_topics')
                ->where('topic_id', $topicId)
                ->count();

            Topic::where('id', $topicId)->update([
                'post_count' => $actualCount,
            ]);
        }
    }
}
