<?php

use Illuminate\Support\Facades\Route;
use Plugins\ContentAudit\Http\Controllers\AuditController;

Route::middleware('api')
    ->group(function (): void {
        // 手动触发审核
        Route::post('audit', [AuditController::class, 'audit'])->name('plugin.ContentAudit.audit');

        // 获取审核日志
        Route::get('logs', [AuditController::class, 'logs'])->name('plugin.ContentAudit.logs');

        // 配置管理（需要管理员权限）
        Route::middleware('auth:api', 'admin')->group(function (): void {
            Route::get('config', [Plugins\ContentAudit\Http\Controllers\ConfigController::class, 'get'])->name('plugin.ContentAudit.config.get');
            Route::put('config', [Plugins\ContentAudit\Http\Controllers\ConfigController::class, 'update'])->name('plugin.ContentAudit.config.update');

            // 队列状态查询（仅查询，不管理）
            Route::prefix('queue')->group(function (): void {
                Route::get('status', [Plugins\ContentAudit\Http\Controllers\QueueController::class, 'status'])->name('plugin.ContentAudit.queue.status');
                // 注意：队列启动/停止应由主程序统一管理，请使用系统队列管理功能
            });
        });
    });
