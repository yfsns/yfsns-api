<?php

namespace Plugins\ContentAudit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Article\Models\Article;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Plugins\ContentAudit\Services\AuditService;

class AuditController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * 获取状态更新器（延迟解析，避免依赖注入问题）
     */
    protected function getStatusUpdater()
    {
        // 暂时注释掉，待重新搭建ContentAudit后启用
        // return app(\App\Modules\Plugin\Contracts\ContentStatusUpdaterInterface::class);
        return null;
    }

    /**
     * 手动触发审核（插件自主处理）.
     */
    public function audit(Request $request): JsonResponse
    {
        $request->validate([
            'content_id' => 'required|integer',
            'content_type' => 'required|string|in:article,post,thread',
        ]);

        try {
            $contentId = $request->input('content_id');
            $contentType = $request->input('content_type');

            // 获取内容对象并处理审核
            $modelClass = match ($contentType) {
                'article' => Article::class,
                'post' => \App\Modules\Post\Models\Post::class,
                'thread' => \App\Modules\Forum\Models\ForumThread::class,
                default => null,
            };

            if (! $modelClass) {
                return response()->json([
                    'code' => 400,
                    'message' => '不支持的内容类型',
                    'data' => null,
                ], 400);
            }

            $content = $modelClass::findOrFail($contentId);

            // 1. 提取内容数据
            $contentData = [
                'id' => $content->id,
                'title' => $content->title ?? '',
                'content' => strip_tags($content->content ?? ''),
                'description' => $content->description ?? ($content->excerpt ?? ''),
            ];

            // 2. 调用审核服务（如果失败会抛出异常）
            try {
                $auditResult = $this->auditService->auditContent($contentData, $contentType);
            } catch (Exception $e) {
                return response()->json([
                    'code' => 500,
                    'message' => '审核服务异常: '.$e->getMessage(),
                    'data' => null,
                ], 500);
            }

            // 3. 确定新状态（接口会自动处理状态转换）
            $newStatus = $this->mapAuditResultToStatus($auditResult);

            // 4. 调用接口更新状态和记录日志
            $this->getStatusUpdater()?->updateStatusAndLog(
                $contentType,
                $content->id,
                $newStatus,
                'ai',
                'ContentAudit',
                $auditResult
            );

            return response()->json([
                'code' => 200,
                'message' => '审核完成',
                'data' => [
                    'status' => $newStatus,
                    'result' => $auditResult,
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('手动审核失败', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '审核异常: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 获取审核日志（从主表查询）.
     */
    public function logs(Request $request): JsonResponse
    {
        $request->validate([
            'content_id' => 'required|integer',
            'content_type' => 'required|string|in:article,post,thread',
        ]);

        try {
            $contentId = $request->input('content_id');
            $contentType = $request->input('content_type');

            // 从统一的 ReviewLog 表查询审核日志
            $modelClass = match ($contentType) {
                'article' => Article::class,
                'post' => \App\Modules\Post\Models\Post::class,
                'thread' => \App\Modules\Forum\Models\ForumThread::class,
                default => null,
            };

            if (! $modelClass) {
                return response()->json([
                    'code' => 400,
                    'message' => '不支持的内容类型',
                    'data' => null,
                ], 400);
            }

            $logs = \App\Modules\Review\Models\ReviewLog::where('reviewable_type', $modelClass)
                ->where('reviewable_id', $contentId)
                ->where('channel', 'ai')
                ->where('plugin_name', 'ContentAudit')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($log) {
                    $result = $log->audit_result ?? [];

                    return [
                        'id' => $log->id,
                        'status' => $result['status'] ?? 'pending',
                        'result' => $result,
                        'created_at' => $log->created_at?->toIso8601String(),
                    ];
                });

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $logs,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取审核日志失败: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 将审核结果映射为内容状态
     *
     * 注意：接口会自动处理状态转换，这里只做简单映射
     */
    protected function mapAuditResultToStatus(array $auditResult): string
    {
        $status = $auditResult['status'] ?? 'pending';

        return match ($status) {
            'pass', 'approved' => 'published',
            'reject', 'rejected' => 'rejected',
            default => 'pending',
        };
    }
}
