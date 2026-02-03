<?php

/*
|--------------------------------------------------------------------------
| 腾讯云短信插件 API 路由
|--------------------------------------------------------------------------
|
| 这里定义腾讯云短信插件的API路由
|
*/

Route::prefix('api/plugins/tencent-sms')->middleware('api')->group(function (): void {
    // 模板同步
    Route::post('templates/sync', function () {
        $syncService = app(\Plugins\TencentSmsPlugin\Services\TencentTemplateSyncService::class);
        $result = $syncService->syncTemplates();
        return response()->json($result);
    });

    // 获取模板列表
    Route::get('templates', function () {
        $syncService = app(\Plugins\TencentSmsPlugin\Services\TencentTemplateSyncService::class);
        $result = $syncService->getLocalTemplates();
        return response()->json($result);
    });

    // 获取单个模板
    Route::get('templates/{templateId}', function ($templateId) {
        $syncService = app(\Plugins\TencentSmsPlugin\Services\TencentTemplateSyncService::class);
        $template = $syncService->findTemplate($templateId);
        if (!$template) {
            return response()->json(['success' => false, 'message' => '模板不存在'], 404);
        }
        return response()->json(['success' => true, 'data' => $template]);
    });

    // 发送测试短信
    Route::post('test/send', function (\Illuminate\Http\Request $request) {
        $phone = $request->input('phone', '18855188912');
        $templateId = $request->input('template_id', '');
        $templateData = $request->input('template_data', []);

        if (empty($templateId)) {
            return response()->json(['success' => false, 'message' => '请提供模板ID'], 400);
        }

        $smsService = app(\Plugins\TencentSmsPlugin\Services\TencentSmsService::class);
        $result = $smsService->send($phone, $templateId, $templateData);
        return response()->json($result);
    });

    // 测试连接
    Route::post('test/connection', function () {
        $smsService = app(\Plugins\TencentSmsPlugin\Services\TencentSmsService::class);
        $config = $smsService->getConfig();
        $result = $smsService->testConnection($config);
        return response()->json($result);
    });

    // 模板管理API（用于配置界面）
    Route::prefix('config')->group(function () {
        // 获取模板列表（用于配置界面展示）
        Route::get('templates', function () {
            $plugin = app(\Plugins\TencentSmsPlugin\Plugin::class);
            $result = $plugin->handleDynamicConfig('TENCENT_SMS_TEMPLATES', ['action' => 'list']);
            return response()->json($result);
        });

        // 同步模板
        Route::post('templates/sync', function () {
            $plugin = app(\Plugins\TencentSmsPlugin\Plugin::class);
            $result = $plugin->handleDynamicConfig('TENCENT_SMS_TEMPLATES', ['action' => 'sync']);
            return response()->json($result);
        });

        // 更新模板状态
        Route::patch('templates/{templateId}/status', function ($templateId, \Illuminate\Http\Request $request) {
            $status = $request->input('status');
            $plugin = app(\Plugins\TencentSmsPlugin\Plugin::class);
            $result = $plugin->handleDynamicConfig('TENCENT_SMS_TEMPLATES', [
                'action' => 'update_status',
                'template_id' => $templateId,
                'status' => $status
            ]);
            return response()->json($result);
        });
    });
});

// Web路由用于管理界面
Route::prefix('admin/plugins/tencent-sms')->middleware(['web', 'auth'])->group(function () {
    // 模板管理页面
    Route::get('templates', function () {
        return view('plugins.tencent-sms.templates.index');
    });
});
