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

namespace App\Modules\Tag\Traits;

use App\Modules\Tag\Models\Tag;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Taggable
{
    /**
     * 获取模型的标签
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(
            Tag::class,
            'taggable',
            'taggables',
            'taggable_id',
            'tag_id'
        )->withTimestamps();
    }

    /**
     * 给模型添加标签
     *
     * @param array|string|Tag $tags
     * @param User|null $user 添加标签的用户
     * @return $this
     */
    public function attachTags($tags, User $user = null)
    {
        $userId = $user ? $user->id : auth()->id();

        if (!$userId) {
            throw new \Exception('必须指定用户才能添加标签');
        }

        $tagIds = $this->resolveTagIds($tags);

        // 只添加不存在的标签关联
        $existingTagIds = $this->tags()->pluck('tags.id')->toArray();
        $newTagIds = array_diff($tagIds, $existingTagIds);

        if (!empty($newTagIds)) {
            // 添加标签关联
            $this->tags()->attach($newTagIds, ['user_id' => $userId]);

            // 更新标签使用次数
            Tag::whereIn('id', $newTagIds)->increment('usage_count');
        }

        return $this;
    }

    /**
     * 从模型移除标签
     *
     * @param array|string|Tag $tags
     * @return $this
     */
    public function detachTags($tags = null)
    {
        $tagIds = $tags ? $this->resolveTagIds($tags) : null;

        if ($tagIds) {
            // 移除特定标签
            $this->tags()->detach($tagIds);

            // 更新标签使用次数
            Tag::whereIn('id', $tagIds)->decrement('usage_count');
        } else {
            // 移除所有标签
            $tagIds = $this->tags()->pluck('tags.id')->toArray();
            $this->tags()->detach();

            if (!empty($tagIds)) {
                Tag::whereIn('id', $tagIds)->decrement('usage_count');
            }
        }

        return $this;
    }

    /**
     * 同步模型的标签（移除旧的，添加新的）
     *
     * @param array|string|Tag $tags
     * @param User|null $user 添加标签的用户
     * @return $this
     */
    public function syncTags($tags, User $user = null)
    {
        $tagIds = $this->resolveTagIds($tags);
        $userId = $user ? $user->id : auth()->id();

        if (!$userId) {
            throw new \Exception('必须指定用户才能同步标签');
        }

        // 获取当前标签IDs
        $currentTagIds = $this->tags()->pluck('tags.id')->toArray();

        // 计算需要移除和添加的标签
        $tagsToRemove = array_diff($currentTagIds, $tagIds);
        $tagsToAdd = array_diff($tagIds, $currentTagIds);

        // 移除不需要的标签
        if (!empty($tagsToRemove)) {
            $this->tags()->detach($tagsToRemove);
            Tag::whereIn('id', $tagsToRemove)->decrement('usage_count');
        }

        // 添加新的标签
        if (!empty($tagsToAdd)) {
            $this->tags()->attach($tagsToAdd, ['user_id' => $userId]);
            Tag::whereIn('id', $tagsToAdd)->increment('usage_count');
        }

        return $this;
    }

    /**
     * 检查模型是否有指定标签
     *
     * @param string|Tag $tag
     * @return bool
     */
    public function hasTag($tag): bool
    {
        $tagId = $this->resolveTagId($tag);
        return $this->tags()->where('tags.id', $tagId)->exists();
    }

    /**
     * 获取模型的标签名称
     *
     * @return array
     */
    public function getTagNames(): array
    {
        return $this->tags->pluck('name')->toArray();
    }

    /**
     * 获取模型的标签IDs
     *
     * @return array
     */
    public function getTagIds(): array
    {
        return $this->tags->pluck('id')->toArray();
    }

    /**
     * 解析标签为ID数组
     *
     * @param array|string|Tag $tags
     * @return array
     */
    protected function resolveTagIds($tags): array
    {
        if (is_string($tags)) {
            $tags = [$tags];
        }

        if ($tags instanceof Tag) {
            return [$tags->id];
        }

        if (!is_array($tags)) {
            return [];
        }

        $tagIds = [];
        foreach ($tags as $tag) {
            $tagIds[] = $this->resolveTagId($tag);
        }

        return array_unique($tagIds);
    }

    /**
     * 解析单个标签为ID
     *
     * @param string|int|Tag $tag
     * @return int
     */
    protected function resolveTagId($tag): int
    {
        if ($tag instanceof Tag) {
            return $tag->id;
        }

        if (is_numeric($tag)) {
            return (int) $tag;
        }

        if (is_string($tag)) {
            // 尝试通过名称或slug查找标签
            $foundTag = Tag::where('name', $tag)
                          ->orWhere('slug', $tag)
                          ->first();

            if (!$foundTag) {
                // 如果标签不存在，自动创建
                $foundTag = Tag::create([
                    'name' => $tag,
                    'slug' => \Illuminate\Support\Str::slug($tag),
                ]);
            }

            return $foundTag->id;
        }

        throw new \InvalidArgumentException('无效的标签参数');
    }
}