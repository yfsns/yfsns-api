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

namespace App\Modules\Post\Services;

use App\Modules\Post\Models\Post;
use App\Modules\User\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StoryService
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * 创建故事（图文）
     */
    public function create(array $data, Request $request): Post
    {
        // 处理文件关联
        $fileIds = $data['file_ids'] ?? [];
        unset($data['file_ids']);

        // 处理位置信息
        if (isset($data['location'])) {
            $data['location_id'] = $this->processLocation($data['location']);
            unset($data['location']);
        }

        // 处理内容（图文内容可以为空）
        $data['content'] = $data['content'] ?? '';

        // 生成HTML内容
        $data['content_html'] = $this->processContentHtml($data['content']);

        $story = Post::create($data);

        // 关联文件
        if (!empty($fileIds)) {
            $story->files()->attach($fileIds);
        }

        // 处理@用户和#话题
        $this->processMentionsAndTopics($story, $request->input('mentions', []), $request->input('topics', []));

        // 清理相关缓存
        $this->clearStoryCache();

        return $story->load(['user', 'files']);
    }

    /**
     * 删除故事（图文）
     */
    public function delete(int $storyId): bool
    {
        $story = Post::findOrFail($storyId);

        // 删除关联数据
        $story->files()->detach();
        $story->likes()->delete();
        $story->collects()->delete();
        $story->comments()->delete();

        $result = $story->delete();

        // 清理相关缓存
        $this->clearStoryCache();

        return $result;
    }

    /**
     * 处理位置信息
     */
    protected function processLocation(array $location): ?int
    {
        try {
            // 这里应该调用LocationService来创建或关联位置
            // 暂时返回null，后续完善
            return null;
        } catch (\Exception $e) {
            Log::warning('处理故事位置信息失败', [
                'location' => $location,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 处理内容HTML
     */
    protected function processContentHtml(string $content): string
    {
        // 这里可以添加内容处理的逻辑，比如：
        // - 转换Markdown为HTML
        // - 处理@用户链接
        // - 处理#话题链接
        // - 过滤危险内容

        // 暂时直接返回原内容
        return nl2br(e($content));
    }

    /**
     * 处理@用户和#话题
     */
    protected function processMentionsAndTopics(Post $story, array $mentions, array $topics): void
    {
        try {
            // 处理@用户
            if (!empty($mentions)) {
                // 这里应该调用MentionService来处理@用户
            }

            // 处理#话题
            if (!empty($topics)) {
                $story->topics()->sync($topics);
            }
        } catch (\Exception $e) {
            Log::warning('处理故事@用户和#话题失败', [
                'story_id' => $story->id,
                'mentions' => $mentions,
                'topics' => $topics,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 清理故事相关缓存
     */
    protected function clearStoryCache(): void
    {
        try {
            // 清理推荐故事缓存
            Cache::forget('recommended_stories');

            // 可以在这里添加更多需要清理的缓存
        } catch (\Exception $e) {
            Log::warning('清理故事缓存失败', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}