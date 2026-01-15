<?php

/**
 * YFSNS社交网络服务系统
 *
 * Copyright (C) 2025 合肥音符信息科技有限公司
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Modules\Wallet\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 积分资源类
 *
 * 统一处理积分相关的各种数据格式化，包括记录、规则和统计数据
 */
class PointsResource extends JsonResource
{
    /**
     * 数据类型标识
     */
    protected string $resourceType = 'record';

    /**
     * 设置资源类型
     */
    public function setResourceType(string $type): self
    {
        $this->resourceType = $type;
        return $this;
    }

    /**
     * 创建规则资源
     */
    public static function rule($resource): self
    {
        return (new self($resource))->setResourceType('rule');
    }

    /**
     * 创建统计资源
     */
    public static function stats($resource): self
    {
        return (new self($resource))->setResourceType('stats');
    }

    public function toArray($request)
    {
        return match ($this->resourceType) {
            'rule' => $this->formatRule(),
            'stats' => $this->formatStats(),
            default => $this->formatRecord(),
        };
    }

    /**
     * 格式化积分记录数据
     */
    protected function formatRecord(): array
    {
        return [
            'id' => (string) $this->id,
            'userId' => (string) $this->user_id,
            'pointsRuleId' => $this->points_rule_id ? (string) $this->points_rule_id : null,
            'amount' => $this->amount,
            'type' => $this->type,
            'description' => $this->description,
            'metadata' => $this->metadata,

            // 兼容字段（方便前端使用）
            'points' => $this->amount,
            'absolutePoints' => abs($this->amount),

            // 关联的积分规则
            'rule' => $this->whenLoaded('pointsRule', function () {
                return [
                    'id' => (string) $this->pointsRule->id,
                    'name' => $this->pointsRule->name,
                    'code' => $this->pointsRule->code,
                    'description' => $this->pointsRule->description,
                ];
            }),

            // 时间信息
            'createdAt' => $this->created_at?->toIso8601String(),
            'createdAtHuman' => $this->created_at?->diffForHumans(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * 格式化积分规则数据
     */
    protected function formatRule(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'triggerType' => $this->trigger_type,
            'action' => $this->action,
            'points' => $this->points,
            'pointsType' => $this->points_type,
            'formula' => $this->formula,
            'maxTimes' => $this->max_times,
            'dailyLimit' => $this->daily_limit,
            'conditions' => $this->conditions,
            'status' => $this->status,
            'startTime' => $this->start_time?->toIso8601String(),
            'endTime' => $this->end_time?->toIso8601String(),
            'priority' => $this->priority,

            // 状态判断
            'isActive' => $this->isActive(),

            // 时间信息
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * 格式化积分统计数据
     */
    protected function formatStats(): array
    {
        $data = is_array($this->resource) ? $this->resource : [];

        return [
            'currentPoints' => $data['current_points'] ?? 0,
            'totalEarned' => $data['total_earned'] ?? 0,
            'totalUsed' => $data['total_used'] ?? 0,
            'todayEarned' => $data['today_earned'] ?? 0,
            'monthlyEarned' => $data['monthly_earned'] ?? 0,
            'availablePoints' => $data['available_points'] ?? 0,
        ];
    }
}
