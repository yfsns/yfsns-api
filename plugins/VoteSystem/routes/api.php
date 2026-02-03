<?php

use Illuminate\Support\Facades\Route;
use Plugins\VoteSystem\Http\Controllers\VoteController;

Route::middleware(['auth:api'])->group(function (): void {
    // 获取投票列表
    Route::get('votes', [VoteController::class, 'index']);

    // 获取单个投票详情
    Route::get('votes/{id}', [VoteController::class, 'show']);

    // 参与投票
    Route::post('votes/{id}/vote', [VoteController::class, 'vote']);

    // 检查用户是否已投票
    Route::get('votes/{id}/check-vote', [VoteController::class, 'checkVote']);
});

// 公开路由（不需要登录）
Route::get('votes/{id}/results', [VoteController::class, 'results'])->middleware('optional_auth');
