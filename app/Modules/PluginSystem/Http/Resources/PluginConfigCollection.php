<?php

namespace App\Modules\PluginSystem\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PluginConfigCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     */
    public $collects = PluginConfigResource::class;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        // 按组重新组织数据
        $grouped = [];
        foreach ($this->collection as $config) {
            $group = $config->resource['group'];
            if (!isset($grouped[$group])) {
                $grouped[$group] = [
                    'name' => $group,
                    'label' => ucfirst($group),
                    'configItems' => [],
                ];
            }
            $grouped[$group]['configItems'][] = $config;
        }

        // 只返回配置组数组，前端直接访问 configGroups
        return array_values($grouped);
    }
}
