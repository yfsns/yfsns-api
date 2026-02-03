<?php

use Illuminate\Support\Facades\Route;
use Plugins\WechatLogin\Http\Controllers\AuthController;

Route::middleware('api')
    ->prefix('api/v1/plugins/wechat')
    ->group(function (): void {
        Route::prefix('auth')->group(function (): void {
            Route::get('url', [AuthController::class, 'getAuthUrl'])->name('plugin.wechat.auth.url');
            Route::get('callback', [AuthController::class, 'callback'])->name('plugin.wechat.auth.callback');

            Route::get('qrcode/url', [AuthController::class, 'getQrCodeLoginUrl'])->name('plugin.wechat.auth.qrcode.url');
            Route::get('qrcode/callback', [AuthController::class, 'qrCodeCallback'])->name('plugin.wechat.auth.qrcode.callback');

            Route::middleware('auth:api')->group(function (): void {
                Route::post('bind', [AuthController::class, 'bind'])->name('plugin.wechat.auth.bind');
                Route::post('unbind', [AuthController::class, 'unbind'])->name('plugin.wechat.auth.unbind');
                Route::get('bind/status', [AuthController::class, 'checkBindStatus'])->name('plugin.wechat.auth.bind.status');
                Route::get('jsapi/config', [AuthController::class, 'getJsapiConfig'])->name('plugin.wechat.auth.jsapi.config');
            });
        });
    });
