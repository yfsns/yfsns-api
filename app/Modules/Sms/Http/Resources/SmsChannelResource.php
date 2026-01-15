<?php

namespace App\Modules\Sms\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsChannelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'name' => $this->resource['name'],
            'driver' => $this->resource['driver'],
            'status' => $this->resource['status'],
            'config' => $this->resource['config'],
            'createdAt' => $this->resource['created_at'],
            'updatedAt' => $this->resource['updated_at'],
        ];
    }
}
