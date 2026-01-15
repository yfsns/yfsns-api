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

use function dirname;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminReportService
{
    /**
     * 获取举报列表.
     *
     * @param array $params 查询参数
     */
    public function getList(array $params): LengthAwarePaginator
    {
        $query = Report::with(['reporter:id,username,nickname,avatar', 'reportable']);

        // 处理筛选条件
        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (! empty($params['type'])) {
            $query->where('type', $params['type']);
        }
        if (! empty($params['date_range'])) {
            $query->whereBetween('created_at', $params['date_range']);
        }

        // 处理排序
        $orderBy = $params['order_by'] ?? 'created_at';
        $orderDirection = $params['order_direction'] ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);

        // 分页参数处理
        $perPage = (int) ($params['per_page'] ?? 15);
        $page = $params['page'] ?? null;

        if ($page !== null) {
            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        return $query->paginate($perPage);
    }

    /**
     * 获取举报详情.
     *
     * @param int $id 举报ID
     */
    public function getDetail(int $id): array
    {
        $report = Report::with(['reporter', 'reported'])->findOrFail($id);

        return $report->toArray();
    }

    /**
     * 处理举报.
     *
     * @param int   $id   举报ID
     * @param array $data 处理数据
     */
    public function handle(int $id, array $data): array
    {
        $report = Report::findOrFail($id);

        DB::transaction(function () use ($report, $data): void {
            $report->update([
                'status' => $data['status'],
                'handled_by' => auth()->id(),
                'handled_at' => now(),
                'result' => $data['result'] ?? null,
                'remark' => $data['remark'] ?? null,
            ]);

            // 如果举报属实，可能需要执行其他操作
            if ($data['status'] == 3) { // 3-已处理（属实）
                $this->handleConfirmedReport($report, $data);
            }
        });

        return $report->fresh()->toArray();
    }

    /**
     * 批量处理举报.
     *
     * @param array $data 处理数据
     */
    public function batchHandle(array $data): array
    {
        $ids = $data['ids'] ?? [];
        $status = $data['status'];
        $handlingResult = $data['handling_result'] ?? null;
        $remark = $data['remark'] ?? null;

        $successCount = 0;
        $failedCount = 0;

        foreach ($ids as $id) {
            $this->handle($id, [
                'status' => $status,
                'handling_result' => $handlingResult,
                'remark' => $remark,
            ]);
            $successCount++;
        }

        return [
            'success_count' => $successCount,
            'failed_count' => $failedCount,
        ];
    }

    /**
     * 导出举报数据.
     *
     * @param array $params 导出参数
     */
    public function export(array $params): BinaryFileResponse
    {
        $data = $this->getList($params);
        $filename = 'reports_' . date('YmdHis') . '.xlsx';

        // 这里需要实现导出逻辑，可以使用 Laravel Excel 等包
        // 示例代码仅作参考
        $path = storage_path('app/public/exports/' . $filename);

        // 确保目录存在
        if (! file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        // TODO: 实现Excel导出逻辑
        // 这里应该使用 Laravel Excel 等包来实现实际的导出功能

        return response()->download($path, $filename);
    }

    /**
     * 获取举报统计数据.
     *
     * 状态说明：
     * 1-待处理, 2-处理中, 3-已处理, 4-已驳回
     */
    public function getStatistics(): array
    {
        $total = Report::count();
        $pending = Report::where('status', 1)->count(); // 待处理
        $handled = Report::whereIn('status', [3, 4])->count(); // 已处理 + 已驳回
        $inProgress = Report::where('status', 2)->count(); // 处理中
        $rejected = Report::where('status', 4)->count(); // 已驳回
        $todayCount = Report::whereDate('created_at', today())->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'inProgress' => $inProgress,
            'handled' => $handled,
            'rejected' => $rejected,
            'todayCount' => $todayCount,
        ];
    }

    /**
     * 处理已确认的举报.
     *
     * @param Report $report 举报记录
     * @param array  $data   处理数据
     */
    protected function handleConfirmedReport(Report $report, array $data): void
    {
        // 根据举报类型执行不同的处理逻辑
        switch ($report->type) {
            case 'post':
                // 处理帖子举报
                break;
            case 'comment':
                // 处理评论举报
                break;
            case 'user':
                // 处理用户举报
                break;
                // 其他类型的处理逻辑
        }
    }
}
