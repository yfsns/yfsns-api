<?php

namespace Plugins\TestPlugin;

use App\Modules\PluginSystem\BaseDeclarativePlugin;

class Plugin extends BaseDeclarativePlugin
{
    public function __construct()
    {
        $this->name = 'testplugin';
        $this->version = '1.0.0';
        $this->description = '测试插件';
        $this->author = 'Test';
    }
}
