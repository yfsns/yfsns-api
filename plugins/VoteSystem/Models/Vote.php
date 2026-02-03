<?php

namespace Plugins\VoteSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vote extends Model
{
    protected $table = 'plug_vote_votes';

    protected $fillable = [
        'title',
        'description',
        'type',
        'options',
        'user_id',
        'start_time',
        'end_time',
        'is_active',
        'allow_guest',
        'show_results',
        'require_login',
        'max_votes',
        'total_votes',
        'total_participants',
        'settings',
    ];

    protected $casts = [
        'options' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
        'allow_guest' => 'boolean',
        'show_results' => 'boolean',
        'require_login' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * 关联创建者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * 关联投票选项
     */
    public function voteOptions(): HasMany
    {
        return $this->hasMany(VoteOption::class, 'vote_id');
    }

    /**
     * 关联投票记录
     */
    public function voteRecords(): HasMany
    {
        return $this->hasMany(VoteRecord::class, 'vote_id');
    }

    /**
     * 检查投票是否进行中
     */
    public function isOngoing(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->start_time && $now->lt($this->start_time)) {
            return false; // 未开始
        }

        if ($this->end_time && $now->gt($this->end_time)) {
            return false; // 已结束
        }

        return true;
    }

    /**
     * 检查用户是否已投票
     */
    public function hasUserVoted(?int $userId, ?string $ipAddress = null): bool
    {
        $query = $this->voteRecords();

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($ipAddress) {
            $query->where('ip_address', $ipAddress);
        }

        return $query->exists();
    }

    /**
     * 获取投票结果
     */
    public function getResults(): array
    {
        $results = [];

        foreach ($this->voteOptions as $option) {
            $results[] = [
                'id' => $option->id,
                'title' => $option->title,
                'description' => $option->description,
                'image' => $option->image,
                'votes_count' => $option->votes_count,
                'percentage' => $this->total_votes > 0 ? round(($option->votes_count / $this->total_votes) * 100, 2) : 0,
            ];
        }

        return $results;
    }

    /**
     * 增加投票数
     */
    public function incrementVoteCount(int $count = 1): void
    {
        $this->increment('total_votes', $count);
    }

    /**
     * 增加参与人数
     */
    public function incrementParticipantCount(): void
    {
        $this->increment('total_participants');
    }
}
