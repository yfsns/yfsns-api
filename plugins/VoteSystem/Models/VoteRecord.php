<?php

namespace Plugins\VoteSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteRecord extends Model
{
    protected $table = 'plug_vote_records';

    protected $fillable = [
        'vote_id',
        'user_id',
        'ip_address',
        'user_agent',
        'options',
        'voted_at',
    ];

    protected $casts = [
        'options' => 'array',
        'voted_at' => 'datetime',
    ];

    /**
     * 关联投票
     */
    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class, 'vote_id');
    }

    /**
     * 关联用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * 获取选择的选项
     */
    public function getSelectedOptions(): array
    {
        return VoteOption::whereIn('id', $this->options ?? [])->get();
    }
}
