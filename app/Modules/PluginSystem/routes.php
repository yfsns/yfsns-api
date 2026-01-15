<?php

use App\Modules\PluginSystem\Http\Controllers\PluginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PluginSystem Routes
|--------------------------------------------------------------------------
|
| Here is where you can register routes for the PluginSystem module.
| These routes are loaded by the PluginSystemServiceProvider.
|
*/

// 插件状态管理API
Route::get('/api/admin/plugins', [PluginController::class, 'index'])->name('plugins.index');

// 插件安装和卸载
Route::post('/api/admin/plugins/{pluginName}/install', [PluginController::class, 'install'])->name('plugins.install');
Route::delete('/api/admin/plugins/{pluginName}', [PluginController::class, 'uninstall'])->name('plugins.uninstall');

// 插件启用和禁用
Route::post('/api/admin/plugins/{pluginName}/enable', [PluginController::class, 'enable'])->name('plugins.enable');
Route::post('/api/admin/plugins/{pluginName}/disable', [PluginController::class, 'disable'])->name('plugins.disable');

// 插件配置管理
Route::get('/api/admin/plugins/{pluginName}/config', [PluginController::class, 'getConfig'])->name('plugins.config.get');
Route::put('/api/admin/plugins/{pluginName}/config', [PluginController::class, 'updateConfig'])->name('plugins.config.update');
Route::post('/api/admin/plugins/{pluginName}/config/actions', [PluginController::class, 'executeConfigAction'])->name('plugins.config.action');
Route::get('/api/admin/plugins/{pluginName}/config/data-tables/{tableKey}', [PluginController::class, 'getConfigDataTable'])->name('plugins.config.data-table');

// 插件安全检查
Route::get('/api/admin/plugins/{pluginName}/security-check', [PluginController::class, 'securityCheck'])->name('plugins.security.check');

// 插件发现管理
Route::post('/api/admin/plugins/discover', [PluginController::class, 'discover'])->name('plugins.discover');
Route::get('/api/admin/plugins/discovery-status', [PluginController::class, 'getDiscoveryStatus'])->name('plugins.discovery.status');
Route::post('/api/admin/plugins/rescan', [PluginController::class, 'rescan'])->name('plugins.rescan');
Route::post('/api/admin/plugins/{pluginName}/discover', [PluginController::class, 'discoverSingle'])->name('plugins.discover.single');
