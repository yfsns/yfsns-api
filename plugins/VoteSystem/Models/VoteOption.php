<?php

namespace Plugins\VoteSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteOption extends Model
{
    protected $table = 'plug_vote_options';

    protected $fillable = [
        'vote_id',
        'title',
        'description',
        'image',
        'votes_count',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * 关联投票
     */
    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class, 'vote_id');
    }

    /**
     * 增加投票数
     */
    public function incrementVoteCount(int $count = 1): void
    {
        $this->increment('votes_count', $count);
    }

    /**
     * 获取百分比
     */
    public function getPercentageAttribute(): float
    {
        if ($this->vote && $this->vote->total_votes > 0) {
            return round(($this->votes_count / $this->vote->total_votes) * 100, 2);
        }

        return 0.0;
    }
}
