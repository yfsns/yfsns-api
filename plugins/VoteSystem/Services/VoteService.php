<?php

namespace Plugins\VoteSystem\Services;

use Illuminate\Support\Facades\DB;
use Plugins\VoteSystem\Models\Vote;
use Plugins\VoteSystem\Models\VoteOption;
use Plugins\VoteSystem\Models\VoteRecord;

class VoteService
{
    /**
     * 创建投票
     */
    public function createVote(array $data): Vote
    {
        return DB::transaction(function () use ($data) {
            // 创建投票
            $vote = Vote::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'],
                'user_id' => $data['user_id'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'allow_guest' => $data['allow_guest'] ?? false,
                'show_results' => $data['show_results'] ?? true,
                'require_login' => $data['require_login'] ?? true,
                'max_votes' => $data['max_votes'] ?? 1,
                'settings' => $data['settings'] ?? [],
            ]);

            // 创建投票选项
            $this->createVoteOptions($vote, $data['options']);

            return $vote->load('voteOptions');
        });
    }

    /**
     * 更新投票
     */
    public function updateVote(Vote $vote, array $data): Vote
    {
        return DB::transaction(function () use ($vote, $data) {
            // 更新投票信息
            $vote->update([
                'title' => $data['title'] ?? $vote->title,
                'description' => $data['description'] ?? $vote->description,
                'start_time' => $data['start_time'] ?? $vote->start_time,
                'end_time' => $data['end_time'] ?? $vote->end_time,
                'allow_guest' => $data['allow_guest'] ?? $vote->allow_guest,
                'show_results' => $data['show_results'] ?? $vote->show_results,
                'require_login' => $data['require_login'] ?? $vote->require_login,
                'max_votes' => $data['max_votes'] ?? $vote->max_votes,
                'settings' => $data['settings'] ?? $vote->settings,
            ]);

            // 如果提供了新的选项，更新选项
            if (isset($data['options'])) {
                $this->updateVoteOptions($vote, $data['options']);
            }

            return $vote->load('voteOptions');
        });
    }

    /**
     * 创建投票选项
     */
    protected function createVoteOptions(Vote $vote, array $options): void
    {
        $optionData = [];
        $sortOrder = 0;

        foreach ($options as $option) {
            $optionData[] = [
                'vote_id' => $vote->id,
                'title' => $option['title'],
                'description' => $option['description'] ?? null,
                'image' => $option['image'] ?? null,
                'sort_order' => $sortOrder++,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        VoteOption::insert($optionData);
    }

    /**
     * 更新投票选项
     */
    protected function updateVoteOptions(Vote $vote, array $options): void
    {
        // 删除现有的选项
        $vote->voteOptions()->delete();

        // 创建新的选项
        $this->createVoteOptions($vote, $options);
    }

    /**
     * 投票
     */
    public function castVote(Vote $vote, array $optionIds, ?int $userId, ?string $ipAddress): array
    {
        // 检查投票是否可以进行
        if (!$vote->isOngoing()) {
            return ['success' => false, 'message' => '投票未开始或已结束'];
        }

        // 检查用户是否已投票
        if ($this->hasUserVoted($vote, $userId, $ipAddress)) {
            return ['success' => false, 'message' => '您已经参与过此投票'];
        }

        // 检查选项数量
        if (count($optionIds) > $vote->max_votes) {
            return ['success' => false, 'message' => "最多只能选择{$vote->max_votes}个选项"];
        }

        // 检查选项是否属于此投票且有效
        $validOptions = VoteOption::where('vote_id', $vote->id)
            ->whereIn('id', $optionIds)
            ->where('is_active', true)
            ->count();

        if ($validOptions !== count($optionIds)) {
            return ['success' => false, 'message' => '选择的选项无效'];
        }

        return DB::transaction(function () use ($vote, $optionIds, $userId, $ipAddress) {
            // 创建投票记录
            $record = VoteRecord::create([
                'vote_id' => $vote->id,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => request()->userAgent(),
                'options' => $optionIds,
                'voted_at' => now(),
            ]);

            // 更新选项投票数
            VoteOption::whereIn('id', $optionIds)->increment('votes_count');

            // 更新投票统计
            $vote->incrementVoteCount(count($optionIds));

            // 如果是第一次参与，增加参与人数
            if (!$this->hasUserVoted($vote, $userId, $ipAddress)) {
                $vote->incrementParticipantCount();
            }

            return ['success' => true, 'record' => $record];
        });
    }

    /**
     * 检查用户是否已投票
     */
    public function hasUserVoted(Vote $vote, ?int $userId, ?string $ipAddress): bool
    {
        $query = VoteRecord::where('vote_id', $vote->id);

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($ipAddress && $vote->allow_guest) {
            $query->where('ip_address', $ipAddress);
        } else {
            return false;
        }

        return $query->exists();
    }

    /**
     * 删除投票
     */
    public function deleteVote(Vote $vote): bool
    {
        // 检查是否有投票记录
        if ($vote->total_votes > 0) {
            return false;
        }

        return DB::transaction(function () use ($vote) {
            // 删除选项
            $vote->voteOptions()->delete();

            // 删除投票
            return $vote->delete();
        });
    }

    /**
     * 获取投票统计
     */
    public function getVoteStats(Vote $vote): array
    {
        return [
            'total_votes' => $vote->total_votes,
            'total_participants' => $vote->total_participants,
            'options_stats' => $vote->voteOptions->map(function ($option) {
                return [
                    'id' => $option->id,
                    'title' => $option->title,
                    'votes_count' => $option->votes_count,
                    'percentage' => $option->getPercentageAttribute(),
                ];
            }),
            'recent_votes' => $vote->voteRecords()
                ->with('user')
                ->orderBy('voted_at', 'desc')
                ->limit(10)
                ->get(),
        ];
    }
}
