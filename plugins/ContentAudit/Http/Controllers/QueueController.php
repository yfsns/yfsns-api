<?php

namespace Plugins\ContentAudit\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function sprintf;

class QueueController extends Controller
{
    /**
     * 获取审核队列状态（仅查询，不管理）.
     *
     * 注意：队列处理器应由主程序统一管理，插件只负责查询状态
     */
    public function status(): JsonResponse
    {
        try {
            // 检查队列任务数量
            $pendingCount = DB::table('jobs')
                ->where('queue', 'audit')
                ->count();

            $failedCount = DB::table('jobs_failed')
                ->where('queue', 'audit')
                ->count();

            // 检查队列处理器是否运行（通过检查进程）
            $isRunning = $this->checkQueueWorkerRunning();

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'isRunning' => $isRunning,
                    'pendingCount' => $pendingCount,
                    'failedCount' => $failedCount,
                    'queue' => 'audit',
                    'tip' => '队列处理器应由主程序统一管理，请使用系统队列管理功能启动/停止队列',
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('获取队列状态失败', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '获取队列状态失败: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * @deprecated 队列处理器应由主程序统一管理，此方法已废弃
     */
    public function start(Request $request): JsonResponse
    {
        try {
            // 检查是否已经在运行
            if ($this->checkQueueWorkerRunning()) {
                return response()->json([
                    'code' => 200,
                    'message' => '队列处理器已在运行',
                    'data' => [
                        'message' => '队列处理器已在运行',
                        'isRunning' => true,
                    ],
                ], 200);
            }

            // 在后台启动队列处理器
            $command = 'php artisan queue:work --queue=audit --sleep=3 --tries=10 --timeout=120';

            // 如果是 Docker 环境
            if (file_exists(base_path('deploy/docker-compose.yml'))) {
                // 使用 sh -c 配合 & 在后台运行
                $dockerComposeFile = base_path('deploy/docker-compose.yml');
                $dockerCommand = sprintf(
                    'docker-compose -f %s exec -T app sh -c "%s > /dev/null 2>&1 &"',
                    escapeshellarg($dockerComposeFile),
                    $command
                );
                exec($dockerCommand, $output, $returnCode);
                Log::info('启动队列处理器命令', [
                    'command' => $dockerCommand,
                    'returnCode' => $returnCode,
                    'output' => $output,
                ]);
            } else {
                // 非 Docker 环境，使用 nohup
                exec("nohup {$command} > /dev/null 2>&1 &", $output, $returnCode);
            }

            // 等待一下，检查是否启动成功
            sleep(2);
            $isRunning = $this->checkQueueWorkerRunning();

            if ($isRunning) {
                return response()->json([
                    'code' => 200,
                    'message' => '队列处理器启动成功',
                    'data' => [
                        'isRunning' => true,
                        'message' => '队列处理器启动成功',
                    ],
                ], 200);
            }
            // 如果启动失败，提供手动启动的命令和说明
            $dockerComposeFile = base_path('deploy/docker-compose.yml');
            $manualCommand = file_exists($dockerComposeFile)
                ? "docker-compose -f {$dockerComposeFile} exec app {$command}"
                : $command;

            // 后台运行命令（用于手动执行）
            $backgroundCommand = file_exists($dockerComposeFile)
                ? "docker-compose -f {$dockerComposeFile} exec -d app {$command}"
                : "nohup {$command} > /dev/null 2>&1 &";

            return response()->json([
                'code' => 500,
                'message' => '队列处理器启动失败，请手动执行以下命令启动队列:',
                'data' => [
                    'isRunning' => false,
                    'manualCommand' => $manualCommand,
                    'backgroundCommand' => $backgroundCommand,
                    'tip' => '建议使用 Supervisor 或 systemd 来管理队列进程，确保自动重启',
                ],
            ], 500);
        } catch (Exception $e) {
            Log::error('启动队列处理器失败', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '启动队列处理器失败: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 停止队列处理器.
     */
    public function stop(): JsonResponse
    {
        try {
            // 查找并停止队列处理器进程
            // 先找到进程 ID，然后 kill
            $findCommand = 'ps aux | grep "[q]ueue:work.*audit" | awk \'{print $2}\'';

            // 如果是 Docker 环境
            if (file_exists(base_path('deploy/docker-compose.yml'))) {
                $dockerFindCommand = 'docker-compose -f ' . base_path('deploy/docker-compose.yml') . ' exec app ' . $findCommand;
                exec($dockerFindCommand, $pids, $findReturnCode);

                if ($findReturnCode === 0 && ! empty($pids)) {
                    // 杀死所有找到的进程
                    foreach ($pids as $pid) {
                        $pid = trim($pid);
                        if (is_numeric($pid)) {
                            $killCommand = 'docker-compose -f ' . base_path('deploy/docker-compose.yml') . " exec app kill {$pid}";
                            exec($killCommand, $killOutput, $killReturnCode);
                        }
                    }
                }
            } else {
                exec($findCommand, $pids, $findReturnCode);
                if ($findReturnCode === 0 && ! empty($pids)) {
                    foreach ($pids as $pid) {
                        $pid = trim($pid);
                        if (is_numeric($pid)) {
                            exec("kill {$pid}", $killOutput, $killReturnCode);
                        }
                    }
                }
            }

            // 等待一下，检查是否停止成功
            sleep(1);
            $isRunning = $this->checkQueueWorkerRunning();

            return response()->json([
                'code' => 200,
                'message' => $isRunning ? '队列处理器停止失败' : '队列处理器已停止',
                'data' => [
                    'isRunning' => $isRunning,
                    'message' => $isRunning ? '队列处理器停止失败，可能仍在运行' : '队列处理器已停止',
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('停止队列处理器失败', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '停止队列处理器失败: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 重试失败的任务
     */
    public function retryFailed(): JsonResponse
    {
        try {
            $count = Artisan::call('queue:retry', ['id' => 'all']);

            return response()->json([
                'code' => 200,
                'message' => '失败任务已重新加入队列',
                'data' => [
                    'message' => '失败任务已重新加入队列',
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('重试失败任务失败', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '重试失败任务失败: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 清空队列.
     */
    public function clear(): JsonResponse
    {
        try {
            // 删除所有待处理的审核任务
            $deleted = DB::table('jobs')
                ->where('queue', 'audit')
                ->delete();

            return response()->json([
                'code' => 200,
                'message' => "已清空 {$deleted} 个待处理任务",
                'data' => [
                    'deleted' => $deleted,
                    'message' => "已清空 {$deleted} 个待处理任务",
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('清空队列失败', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 500,
                'message' => '清空队列失败: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 检查队列处理器是否运行.
     */
    protected function checkQueueWorkerRunning(): bool
    {
        try {
            // 使用 ps 命令检查进程（兼容性更好）
            $command = 'ps aux | grep "[q]ueue:work.*audit"';

            // 如果是 Docker 环境
            if (file_exists(base_path('deploy/docker-compose.yml'))) {
                $dockerCommand = 'docker-compose -f ' . base_path('deploy/docker-compose.yml') . ' exec app ' . $command;
                exec($dockerCommand, $output, $returnCode);
            } else {
                exec($command, $output, $returnCode);
            }

            // 检查输出中是否包含 queue:work
            $found = false;
            foreach ($output as $line) {
                if (strpos($line, 'queue:work') !== false && strpos($line, 'audit') !== false) {
                    $found = true;

                    break;
                }
            }

            return $found;
        } catch (Exception $e) {
            Log::debug('检查队列处理器状态失败', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
