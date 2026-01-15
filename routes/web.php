<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group, making them accessible
| via web browsers.
|
*/

// 首页 - API服务说明
Route::get('/', function () {
    return response()->json([
        'name' => 'YFSNS API',
        'version' => '1.0.0',
        'description' => 'YFSNS社交网络服务系统API',
    ]);
});

// SPA路由支持 - 后台管理页面 (Laravel处理方式)
// 这个路由必须放在最后，作为fallback
Route::get('/admin/{any?}', function () {
    $indexPath = public_path('admin/index.html');
    if (file_exists($indexPath)) {
        return file_get_contents($indexPath);
    }
    return response('Admin application not found', 404);
})->where('any', '.*');
