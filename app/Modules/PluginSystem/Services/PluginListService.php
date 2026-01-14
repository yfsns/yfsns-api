<?php

namespace App\Modules\PluginSystem\Services;

use App\Modules\PluginSystem\Models\PluginInstallation;

/**
 * 插件列表服务
 *
 * 负责插件列表的查询逻辑，数据格式化由模型负责
 */
class PluginListService
{
    /**
     * 获取格式化的插件列表
     */
    public function getFormattedPluginList(): array
    {
        return PluginInstallation::orderedByName()
            ->get()
            ->map(function($plugin) {
                return $plugin->toArrayForList();
            })
            ->toArray();
    }
}
