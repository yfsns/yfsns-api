<?php

use Illuminate\Support\Facades\Route;
use Plugins\HhhkOss\Http\Controllers\OssController;
use Plugins\HhhkOss\Http\Controllers\ConfigController;

/*
|--------------------------------------------------------------------------
| HhhkOss Plugin API Routes
|--------------------------------------------------------------------------
|
| 这里定义了HhhkOss插件的API路由
|
*/

Route::middleware(['api', 'auth:api'])->prefix('oss')->group(function () {

    // 文件上传
    Route::post('/upload', [OssController::class, 'upload'])->name('hhhk-oss.upload');

    // 文件删除
    Route::delete('/delete', [OssController::class, 'delete'])->name('hhhk-oss.delete');

    // 获取文件URL
    Route::get('/url', [OssController::class, 'getUrl'])->name('hhhk-oss.url');

    // 获取OSS配置
    Route::get('/config', [OssController::class, 'getConfig'])->name('hhhk-oss.config');

});

// OSS配置管理路由（需要管理员权限）
Route::middleware(['api', 'auth:api', 'admin'])->prefix('oss-config')->group(function () {

    // 获取配置表单结构和值
    Route::get('/', [ConfigController::class, 'get'])->name('hhhk-oss-config.get');

    // 更新配置
    Route::put('/', [ConfigController::class, 'update'])->name('hhhk-oss-config.update');

    // 测试连接
    Route::post('/test', [ConfigController::class, 'testConnection'])->name('hhhk-oss-config.test');

    // 重置配置
    Route::post('/reset', [ConfigController::class, 'reset'])->name('hhhk-oss-config.reset');

});
