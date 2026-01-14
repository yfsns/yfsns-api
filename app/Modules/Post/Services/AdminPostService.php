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

use App\Http\Exceptions\BusinessException;
use App\Modules\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 管理员动态服务类
 *
 * 处理管理员相关的动态管理操作，包括列表查询、审核、更新、删除等
 */
class AdminPostService
{
    /**
     * 获取动态列表（管理员用）
     *
     * @param array $params 查询参数
     * @return LengthAwarePaginator
     */
    public function getList(array $params = []): LengthAwarePaginator
    {
        $query = Post::query();

        // 按用户ID筛选
        if (! empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }

        // 按状态筛选
        if (array_key_exists('status', $params) && $params['status'] !== null) {
            $query->where('status', $params['status']);
        }

        // 按类型筛选
        if (! empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        // 关键词搜索
        if (! empty($params['keyword'])) {
            $query->where('content', 'like', "%{$params['keyword']}%");
        }

        // 日期范围筛选
        if (! empty($params['start_date'])) {
            $query->whereDate('created_at', '>=', $params['start_date']);
        }
        if (! empty($params['end_date'])) {
            $query->whereDate('created_at', '<=', $params['end_date']);
        }

        // 预加载关联数据并排序
        $query->with(['user'])->ordered();

        // 分页参数处理
        $perPage = (int) ($params['per_page'] ?? 10);
        $page = $params['page'] ?? null;

        if ($page !== null) {
            $result = $query->paginate($perPage, ['*'], 'page', $page);
        } else {
            $result = $query->paginate($perPage);
        }

        return $result;
    }

    /**
     * 获取动态详情（管理员用）
     *
     * @param int $id 动态ID
     * @return Post
     * @throws BusinessException
     */
    public function getDetail(int $id): Post
    {
        $post = Post::find($id);

        if (!$post) {
            throw new BusinessException('动态不存在');
        }

        return $post;
    }

    /**
     * 更新动态（管理员用）
     *
     * @param int   $id   动态ID
     * @param array $data 更新数据
     * @return Post
     * @throws BusinessException
     */
    public function update(int $id, array $data): Post
    {
        $post = Post::findOrFail($id);

        // 确保 status 字段被正确传递
        $updateData = $data;
        if (isset($updateData['status'])) {
            $updateData['status'] = (int) $updateData['status'];
        }

        $post->update($updateData);

        return $post->fresh();
    }

    /**
     * 审核动态
     *
     * @param int   $id   动态ID
     * @param array $data 审核数据
     * @return Post
     * @throws BusinessException
     */
    public function reviewPost(int $id, array $data): Post
    {
        $post = Post::findOrFail($id);

        $status = (int) $data['status'];

        if (! in_array($status, [
            Post::STATUS_PENDING,
            Post::STATUS_PUBLISHED,
            Post::STATUS_REJECTED,
        ], true)) {
            throw new BusinessException('无效的审核状态');
        }

        // 如果状态相同，直接返回
        if ($status === (int) $post->status) {
            return $post;
        }

        // 使用 HasReviewable trait 提供的便捷方法
        if ($status === Post::STATUS_PUBLISHED) {
            $post->approve($data['remark'] ?? null, auth()->id());
        } elseif ($status === Post::STATUS_REJECTED) {
            $post->reject($data['remark'] ?? null, auth()->id());
        }
        // 对于 STATUS_PENDING，不需要额外操作

        return $post->fresh();
    }

    /**
     * 删除动态（管理员用）
     *
     * @param int $id 动态ID
     * @return bool
     * @throws BusinessException
     */
    public function delete(int $id): bool
    {
        $post = Post::findOrFail($id);

        // 软删除动态及其关联文件
        $post->files()->detach();
        return $post->delete();
    }

    /**
     * 批量审核动态
     *
     * @param array $reviews 审核数据数组
     * @return array
     */
    public function batchReview(array $reviews): array
    {
        $results = [];

        foreach ($reviews as $review) {
            try {
                $post = $this->reviewPost($review['id'], $review);
                $results[] = [
                    'id' => $review['id'],
                    'success' => true,
                    'status' => $post->status,
                    'message' => '审核成功',
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'id' => $review['id'],
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * 批量删除动态
     *
     * @param array $ids 动态ID数组
     * @return array
     */
    public function batchDelete(array $ids): array
    {
        $results = [];

        foreach ($ids as $id) {
            try {
                $this->delete($id);
                $results[] = [
                    'id' => $id,
                    'success' => true,
                    'message' => '删除成功',
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'id' => $id,
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
