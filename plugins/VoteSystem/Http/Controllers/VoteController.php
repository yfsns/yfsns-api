<?php

namespace Plugins\VoteSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\VoteSystem\Models\Vote;
use Plugins\VoteSystem\Services\VoteService;

class VoteController extends Controller
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
        $query = Vote::where('is_active', true);

        // 过滤条件
        if ($request->has('status')) {
            switch ($request->status) {
                case 'ongoing':
                    $query->whereRaw('is_active = 1 AND (start_time IS NULL OR start_time <= NOW()) AND (end_time IS NULL OR end_time >= NOW())');
                    break;
                case 'ended':
                    $query->whereRaw('end_time IS NOT NULL AND end_time < NOW()');
                    break;
                case 'upcoming':
                    $query->whereRaw('start_time IS NOT NULL AND start_time > NOW()');
                    break;
            }
        }

        $votes = $query->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $votes,
        ], 200);
    }

    /**
     * 获取单个投票详情
     */
    public function show(int $id): JsonResponse
    {
        $vote = Vote::with(['user', 'voteOptions' => function ($query) {
            $query->where('is_active', true)->orderBy('sort_order');
        }])->findOrFail($id);

        // 检查权限
        if (! $this->canViewVote($vote)) {
            return response()->json([
                'code' => 403,
                'message' => '无权查看此投票',
                'data' => null,
            ], 403);
        }

        // 检查是否已投票
        $hasVoted = $this->voteService->hasUserVoted($vote, auth()->id(), request()->ip());
        $results = null;

        // 如果可以显示结果且已投票或投票已结束
        if ($vote->show_results && ($hasVoted || ! $vote->isOngoing())) {
            $results = $vote->getResults();
        }

        $data = [
            'vote' => $vote,
            'has_voted' => $hasVoted,
            'can_vote' => $this->canVote($vote),
            'results' => $results,
        ];

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $data,
        ], 200);
    }

    /**
     * 参与投票
     */
    public function vote(Request $request, int $id): JsonResponse
    {
        $vote = Vote::findOrFail($id);

        // 验证投票数据
        $request->validate([
            'options' => 'required|array|min:1|max:' . ($vote->max_votes ?: 10),
            'options.*' => 'integer|exists:plug_vote_options,id',
        ]);

        try {
            $result = $this->voteService->castVote($vote, $request->options, auth()->id(), $request->ip());

            if (! $result['success']) {
                return response()->json([
                    'code' => 400,
                    'message' => $result['message'],
                    'data' => null,
                ], 400);
            }

            return response()->json([
                'code' => 200,
                'message' => '投票成功',
                'data' => [
                    'message' => '投票成功',
                    'vote_record' => $result['record'],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '投票失败：'.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * 检查用户是否已投票
     */
    public function checkVote(int $id): JsonResponse
    {
        $vote = Vote::findOrFail($id);

        $hasVoted = $this->voteService->hasUserVoted($vote, auth()->id(), request()->ip());
        $canVote = $this->canVote($vote);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'has_voted' => $hasVoted,
                'can_vote' => $canVote,
            ],
        ], 200);
    }

    /**
     * 获取投票结果（公开接口）
     */
    public function results(int $id): JsonResponse
    {
        $vote = Vote::findOrFail($id);

        // 检查是否可以查看结果
        if (! $vote->show_results) {
            return response()->json([
                'code' => 403,
                'message' => '此投票不公开结果',
                'data' => null,
            ], 403);
        }

        // 如果投票正在进行中且需要登录后才能查看结果
        if ($vote->isOngoing() && $vote->require_login && ! auth()->check()) {
            return response()->json([
                'code' => 401,
                'message' => '请先登录后查看结果',
                'data' => null,
            ], 401);
        }

        $results = $vote->getResults();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'vote' => $vote,
                'results' => $results,
                'total_votes' => $vote->total_votes,
                'total_participants' => $vote->total_participants,
            ],
        ], 200);
    }

    /**
     * 检查是否有权限查看投票
     */
    protected function canViewVote(Vote $vote): bool
    {
        // 如果需要登录且用户未登录
        if ($vote->require_login && !auth()->check()) {
            return false;
        }

        return true;
    }

    /**
     * 检查是否可以投票
     */
    protected function canVote(Vote $vote): bool
    {
        // 检查投票是否进行中
        if (!$vote->isOngoing()) {
            return false;
        }

        // 检查登录要求
        if ($vote->require_login && !auth()->check()) {
            return false;
        }

        // 检查是否已投票
        if ($this->voteService->hasUserVoted($vote, auth()->id(), request()->ip())) {
            return false;
        }

        return true;
    }
}
