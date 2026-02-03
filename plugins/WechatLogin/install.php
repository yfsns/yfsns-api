<?php

/**
 * WechatLogin 插件安装脚本.
 *
 * 此脚本在插件安装时自动执行，用于：
 * - 验证数据库表是否已创建
 * - 初始化必要的配置
 * - 设置默认数据
 * - 其他安装时的初始化操作
 *
 * 注意：数据库迁移会在安装流程中自动执行，此脚本主要用于额外的初始化工作
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

try {
    // 1. 验证数据库表是否已创建（迁移应该已经执行）
    $tables = [
        'plug_wechatlogin_configs',
        'plug_wechatlogin_user_wechats',
    ];

    $missingTables = [];
    foreach ($tables as $table) {
        if (! Schema::hasTable($table)) {
            $missingTables[] = $table;
        }
    }

    if (! empty($missingTables)) {
        Log::warning('WechatLogin 插件安装：以下表未创建', [
            'tables' => $missingTables,
        ]);
        // 不阻止安装，因为迁移可能稍后执行
    } else {
        Log::info('WechatLogin 插件安装：所有数据库表已创建');
    }

    // 2. 检查是否有已存在的配置（可选：创建默认配置示例）
    if (Schema::hasTable('plug_wechatlogin_configs')) {
        $configCount = DB::table('plug_wechatlogin_configs')->count();
        Log::info('WechatLogin 插件安装：当前已有配置数量', [
            'count' => $configCount,
        ]);

        // 可以在这里添加默认配置示例（如果需要）
        // 注意：实际配置应该由管理员通过管理界面或配置脚本添加
    }

    // 3. 记录安装完成
    Log::info('WechatLogin 插件安装脚本执行完成', [
        'version' => '1.0.0',
        'tables_created' => empty($missingTables),
    ]);

    return true;
} catch (Exception $e) {
    Log::error('WechatLogin 插件安装脚本执行失败', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    return false;
}
