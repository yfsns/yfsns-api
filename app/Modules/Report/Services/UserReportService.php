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

namespace App\Modules\Report\Services;

use App\Modules\Report\Models\Report;
use App\Modules\Comment\Models\Comment;
use App\Modules\Post\Models\Post;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;

class UserReportService
{
    /**
     * 创建举报.
     *
     * @param array $data 举报数据
     */
    public function create(array $data): array
    {
        $report = DB::transaction(function () use ($data) {
            // 根据举报类型解析被举报对象（多态关联）
            [$reportableType, $reportableId] = $this->resolveReportable(
                $data['type'],
                (int) $data['target_id']
            );

            // status 使用数值枚举：1-待处理 2-处理中 3-已处理 4-已驳回
            $report = Report::create([
                'user_id' => auth()->id(),
                'reportable_type' => $reportableType,
                'reportable_id' => $reportableId,
                'type' => $data['type'],
                // content 专门存简短原因，description 存详细描述，方便前端/后台分别展示
                'content' => $data['reason'],
                'description' => $data['description'] ?? null,
                'evidence' => $data['evidence'] ?? null,
                'status' => 1,
            ]);

            // 处理举报证据
            if (! empty($data['evidence'])) {
                $this->handleEvidence($report, $data['evidence']);
            }

            return $report;
        });

        return $report->toArray();
    }

    /**
     * 获取用户的举报历史.
     *
     * @param array $params 查询参数
     */
    public function getHistory(array $params): array
    {
        $query = Report::where('reporter_id', auth()->id());

        // 处理筛选条件
        if (! empty($params['status'])) {
            $status = $this->normalizeStatus($params['status']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }
        if (! empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        // 处理排序
        $orderBy = $params['order_by'] ?? 'created_at';
        $orderDirection = $params['order_direction'] ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);

        // 分页
        $perPage = $params['per_page'] ?? 15;
        $page = $params['page'] ?? 1;

        $result = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'list' => $result->items(),
            'total' => $result->total(),
            'page' => $result->currentPage(),
            'per_page' => $result->perPage(),
        ];
    }

    /**
     * 获取举报详情.
     *
     * @param int $id 举报ID
     */
    public function getDetail(int $id): array
    {
        $report = Report::where('reporter_id', auth()->id())
            ->findOrFail($id);

        return $report->toArray();
    }

    /**
     * 取消举报.
     *
     * @param int $id 举报ID
     */
    public function cancel(int $id): array
    {
        $report = Report::where('reporter_id', auth()->id())
            ->where('status', 1)
            ->findOrFail($id);

        $report->update([
            // 这里将“取消”视为一种处理结果，使用 4-已驳回
            'status' => 4,
        ]);

        return $report->fresh()->toArray();
    }

    /**
     * 将类型+目标ID解析为多态关联字段.
     */
    protected function resolveReportable(string $type, int $targetId): array
    {
        return match ($type) {
            'post' => [Post::class, $targetId],
            'comment' => [Comment::class, $targetId],
            'user' => [User::class, $targetId],
            default => [null, $targetId],
        };
    }

    /**
     * 将字符串/数字状态标准化为数值枚举.
     */
    protected function normalizeStatus(string|int $status): ?int
    {
        if (is_numeric($status)) {
            return (int) $status;
        }

        $map = [
            'pending' => 1,
            'processing' => 2,
            'handled' => 3,
            'rejected' => 4,
            'cancelled' => 4,
        ];

        return $map[$status] ?? null;
    }

    /**
     * 处理举报证据.
     *
     * @param Report $report   举报记录
     * @param array  $evidence 证据数据
     */
    protected function handleEvidence(Report $report, array $evidence): void
    {
        // 处理图片证据
        if (! empty($evidence['images'])) {
            foreach ($evidence['images'] as $image) {
                // 处理图片上传逻辑
            }
        }

        // 处理视频证据
        if (! empty($evidence['videos'])) {
            foreach ($evidence['videos'] as $video) {
                // 处理视频上传逻辑
            }
        }

        // 处理其他类型的证据
        if (! empty($evidence['others'])) {
            foreach ($evidence['others'] as $other) {
                // 处理其他类型证据的逻辑
            }
        }
    }
}
