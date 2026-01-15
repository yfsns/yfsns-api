<?php

namespace App\Modules\Sms\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SmsChannelCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     */
    public $collects = SmsChannelResource::class;

    /**
     * Additional data
     */
    private array $additionalData;

    /**
     * Create a new resource collection.
     */
    public function __construct($resource, array $additionalData = [])
    {
        parent::__construct($resource);
        $this->additionalData = $additionalData;
    }

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'configs' => $this->collection,
            'availableChannels' => $this->formatAvailableChannels($this->additionalData['available_channels'] ?? []),
            'channelStatuses' => $this->formatChannelStatuses($this->additionalData['channel_statuses'] ?? []),
        ];
    }

    /**
     * 格式化可用通道信息
     */
    private function formatAvailableChannels(array $channels): array
    {
        $formatted = [];
        foreach ($channels as $type => $channel) {
            $formatted[$type] = [
                'type' => $channel['type'] ?? $type,
                'name' => $channel['name'] ?? '',
                'capabilities' => $channel['capabilities'] ?? [],
                'isBuiltin' => $channel['is_builtin'] ?? false,
                'configFields' => $this->formatConfigFields($channel['config_fields'] ?? []),
            ];
        }
        return $formatted;
    }

    /**
     * 格式化配置字段
     */
    private function formatConfigFields(array $fields): array
    {
        $formatted = [];
        foreach ($fields as $key => $field) {
            $formatted[$key] = [
                'type' => $field['type'] ?? 'text',
                'label' => $field['label'] ?? $key,
                'required' => $field['required'] ?? false,
                'options' => $field['options'] ?? [],
                'placeholder' => $field['placeholder'] ?? '',
                'description' => $field['description'] ?? '',
            ];
        }
        return $formatted;
    }

    /**
     * 格式化通道状态
     */
    private function formatChannelStatuses(array $statuses): array
    {
        $formatted = [];
        foreach ($statuses as $type => $status) {
            $formatted[$type] = [
                'channelType' => $status['channel_type'] ?? $type,
                'configured' => $status['configured'] ?? false,
                'enabled' => $status['enabled'] ?? false,
                'available' => $status['available'] ?? false,
            ];
        }
        return $formatted;
    }
}
