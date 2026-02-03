<?php

/**
 * WechatLogin 插件卸载脚本.
 *
 * 此脚本在插件卸载时自动执行，用于：
 * - 清理配置数据（可选）
 * - 删除临时文件
 * - 恢复系统设置
 * - 其他卸载时的清理操作
 *
 * 注意：
 * - 数据库表不会被自动删除，如果需要删除表，请在卸载脚本中手动处理
 * - 用户微信绑定数据通常应该保留，以便用户重新安装插件后可以继续使用
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

try {
    // 1. 记录卸载开始
    Log::info('WechatLogin 插件卸载脚本开始执行');

    // 2. 检查数据库表是否存在
    $tables = [
        'plug_wechatlogin_configs',
        'plug_wechatlogin_user_wechats',
    ];

    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            $count = DB::table($table)->count();
            Log::info("WechatLogin 插件卸载：表 {$table} 存在，数据条数: {$count}");
        } else {
            Log::info("WechatLogin 插件卸载：表 {$table} 不存在");
        }
    }

    // 3. 清理配置数据（可选）
    // 注意：通常不建议删除配置数据，以便用户重新安装后可以恢复配置
    // 如果需要清理，可以取消下面的注释
    /*
    if (Schema::hasTable('plug_wechatlogin_configs')) {
        $deleted = DB::table('plug_wechatlogin_configs')->delete();
        Log::info('WechatLogin 插件卸载：已清理配置数据', [
            'deleted_count' => $deleted,
        ]);
    }
    */

    // 4. 清理用户微信绑定数据（可选）
    // 注意：通常不建议删除用户绑定数据，以便用户重新安装后可以继续使用
    // 如果需要清理，可以取消下面的注释
    /*
    if (Schema::hasTable('plug_wechatlogin_user_wechats')) {
        $deleted = DB::table('plug_wechatlogin_user_wechats')->delete();
        Log::info('WechatLogin 插件卸载：已清理用户绑定数据', [
            'deleted_count' => $deleted,
        ]);
    }
    */

    // 5. 删除数据库表（谨慎操作，通常不建议）
    // 如果需要删除表，可以取消下面的注释
    /*
    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            Schema::dropIfExists($table);
            Log::info("WechatLogin 插件卸载：已删除表 {$table}");
        }
    }
    */

    // 6. 记录卸载完成
    Log::info('WechatLogin 插件卸载脚本执行完成', [
        'version' => '1.0.0',
        'note' => '配置和用户绑定数据已保留，如需清理请手动处理',
    ]);

    return true;
} catch (Exception $e) {
    Log::error('WechatLogin 插件卸载脚本执行失败', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    return false;
}
