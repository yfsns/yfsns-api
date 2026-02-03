<?php

namespace Plugins\VoteSystem\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\VoteSystem\Models\Vote;
use Plugins\VoteSystem\Services\VoteService;
use Plugins\VoteSystem\Http\Requests\CreateVoteRequest;
use Plugins\VoteSystem\Http\Requests\UpdateVoteRequest;

class VoteAdminController extends Controller
{
    protected VoteService $voteService;

    public function __construct(VoteService $voteService)
    {
        $this->voteService = $voteService;
    }

    /**
     * 获取投票列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vote::with(['user', 'voteOptions']);

        // 过滤条件
        if ($request->has('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
            }
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $votes = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $votes,
        ], 200);
    }

    /**
     * 创建投票
     */
    public function store(CreateVoteRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['user_id'] = auth()->id();

            $vote = $this->voteService->createVote($data);

            return response()->json([
                'code' => 201,
                'message' => '投票创建成功',
                'data' => $vote,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '创建投票失败：'.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 获取单个投票详情
     */
    public function show(int $id): JsonResponse
    {
        $vote = Vote::with(['user', 'voteOptions', 'voteRecords.user'])
            ->findOrFail($id);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $vote,
        ], 200);
    }

    /**
     * 更新投票
     */
    public function update(UpdateVoteRequest $request, int $id): JsonResponse
    {
        try {
            $vote = Vote::findOrFail($id);
            $data = $request->validated();

            $updatedVote = $this->voteService->updateVote($vote, $data);

            return response()->json([
                'code' => 200,
                'message' => '投票更新成功',
                'data' => $updatedVote,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '投票更新失败：'.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 删除投票
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $vote = Vote::findOrFail($id);

            // 检查是否有投票记录，如果有则不允许删除
            if ($vote->total_votes > 0) {
                return response()->json([
                    'code' => 400,
                    'message' => '该投票已有投票记录，无法删除',
                    'data' => null,
                ], 400);
            }

            $vote->delete();

            return response()->json([
                'code' => 200,
                'message' => '投票删除成功',
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '删除投票失败：'.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 激活投票
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $vote = Vote::findOrFail($id);
            $vote->update(['is_active' => true]);

            return response()->json([
                'code' => 200,
                'message' => '投票激活成功',
                'data' => $vote,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '激活投票失败：'.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 停用投票
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $vote = Vote::findOrFail($id);
            $vote->update(['is_active' => false]);

            return response()->json([
                'code' => 200,
                'message' => '投票停用成功',
                'data' => $vote,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '停用投票失败：'.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 获取投票统计
     */
    public function stats(int $id): JsonResponse
    {
        $vote = Vote::with(['voteOptions', 'voteRecords'])->findOrFail($id);

        $stats = [
            'vote' => $vote,
            'results' => $vote->getResults(),
            'records_count' => $vote->voteRecords()->count(),
            'recent_records' => $vote->voteRecords()
                ->with('user')
                ->orderBy('voted_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $stats,
        ], 200);
    }

    /**
     * 获取总览统计
     */
    public function overviewStats(): JsonResponse
    {
        $stats = [
            'total_votes' => Vote::count(),
            'active_votes' => Vote::where('is_active', true)->count(),
            'total_vote_count' => Vote::sum('total_votes'),
            'total_participants' => Vote::sum('total_participants'),
            'ongoing_votes' => Vote::whereRaw('is_active = 1 AND (start_time IS NULL OR start_time <= NOW()) AND (end_time IS NULL OR end_time >= NOW())')->count(),
        ];

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $stats,
        ], 200);
    }

    /**
     * 获取插件配置
     */
    public function getConfig(): JsonResponse
    {
        $config = config('plugins.vote_system', []);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $config,
        ], 200);
    }

    /**
     * 更新插件配置
     */
    public function updateConfig(Request $request): JsonResponse
    {
        $request->validate([
            'keep_data_on_uninstall' => 'boolean',
            'default_allow_guest' => 'boolean',
            'default_show_results' => 'boolean',
            'default_require_login' => 'boolean',
            'max_options_per_vote' => 'integer|min:2|max:50',
            'max_votes_per_user' => 'integer|min:1|max:20',
        ]);

        try {
            $config = $request->only([
                'keep_data_on_uninstall',
                'default_allow_guest',
                'default_show_results',
                'default_require_login',
                'max_options_per_vote',
                'max_votes_per_user',
            ]);

            // 保存配置到数据库或文件
            config(['plugins.vote_system' => array_merge(config('plugins.vote_system', []), $config)]);

            return response()->json([
                'code' => 200,
                'message' => '配置更新成功',
                'data' => $config,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '配置更新失败：'.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
