<?php

use App\Modules\File\Controllers\FileController;
use Illuminate\Support\Facades\Route;

// 文件上传相关接口
// 统一文件上传接口（需要认证）
Route::middleware('auth:sanctum')->group(function () {
    Route::post('file/upload', [FileController::class, 'unifiedUpload']);
});
