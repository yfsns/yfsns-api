<?php

namespace App\Modules\PluginSystem\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PluginConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->resource['id'],
            'key' => $this->resource['key'],
            'label' => $this->resource['label'],
            'type' => $this->resource['type'],
            'value' => $this->resource['value'],
            'defaultValue' => $this->resource['default'],
            'options' => $this->resource['options'],
            'description' => $this->resource['description'],
            'group' => $this->resource['group'],
            'order' => $this->resource['order'],
            'required' => $this->resource['required'],
            'validationRules' => $this->resource['validation_rules'],
        ];

        // 添加按钮相关字段
        if ($this->resource['type'] === 'button') {
            $data = array_merge($data, [
                'button_action' => $this->resource['button_action'],
                'button_variant' => $this->resource['button_variant'],
                'button_confirm' => $this->resource['button_confirm'],
                'button_disabled' => $this->resource['button_disabled'],
            ]);
        }

        // 添加数据表格相关字段
        if ($this->resource['type'] === 'data_table') {
            $data = array_merge($data, [
                'data_source' => is_string($this->resource['data_source']) ? json_decode($this->resource['data_source'], true) : $this->resource['data_source'],
                'columns' => is_string($this->resource['table_columns']) ? json_decode($this->resource['table_columns'], true) : $this->resource['table_columns'],
                'operations' => is_string($this->resource['table_operations']) ? json_decode($this->resource['table_operations'], true) : $this->resource['table_operations'],
                'actions' => is_string($this->resource['table_actions']) ? json_decode($this->resource['table_actions'], true) : $this->resource['table_actions'],
                'filters' => is_string($this->resource['table_filters']) ? json_decode($this->resource['table_filters'], true) : $this->resource['table_filters'],
                'search' => is_string($this->resource['table_search']) ? json_decode($this->resource['table_search'], true) : $this->resource['table_search'],
                'pagination' => is_string($this->resource['table_pagination']) ? json_decode($this->resource['table_pagination'], true) : $this->resource['table_pagination'],
                'default_sort' => is_string($this->resource['table_default_sort']) ? json_decode($this->resource['table_default_sort'], true) : $this->resource['table_default_sort'],
            ]);
        }

        // 添加只读标识
        $data['is_readonly'] = $this->resource['is_readonly'] ?? false;

        return $data;
    }
}
