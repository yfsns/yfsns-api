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
 * 优惠券资源类
 *
 * 统一处理优惠券相关的各种数据格式化，包括优惠券模板和用户优惠券
 */
class CouponResource extends JsonResource
{
    /**
     * 数据类型标识
     */
    protected string $resourceType = 'template';

    /**
     * 设置资源类型
     */
    public function setResourceType(string $type): self
    {
        $this->resourceType = $type;
        return $this;
    }

    /**
     * 创建用户优惠券资源
     */
    public static function userCoupon($resource): self
    {
        return (new self($resource))->setResourceType('user_coupon');
    }

    public function toArray($request)
    {
        return match ($this->resourceType) {
            'user_coupon' => $this->formatUserCoupon(),
            default => $this->formatTemplate(),
        };
    }

    /**
     * 格式化优惠券模板数据
     */
    protected function formatTemplate(): array
    {
        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'discountType' => $this->discount_type,
            'discountValue' => $this->discount_value,
            'minAmount' => $this->min_amount,
            'maxDiscount' => $this->max_discount,
            'totalQuantity' => $this->total_quantity,
            'usedQuantity' => $this->used_quantity ?? 0,
            'remainingQuantity' => ($this->total_quantity ?? 0) - ($this->used_quantity ?? 0),
            'startTime' => $this->start_time?->toIso8601String(),
            'endTime' => $this->end_time?->toIso8601String(),
            'status' => $this->status,
            'isActive' => $this->isActive ?? false,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * 格式化用户优惠券数据
     */
    protected function formatUserCoupon(): array
    {
        return [
            'id' => (string) $this->id,
            'userId' => (string) $this->user_id,
            'couponId' => (string) $this->coupon_id,
            'code' => $this->code,
            'status' => $this->status,
            'usedAt' => $this->used_at?->toIso8601String(),
            'expiredAt' => $this->expired_at?->toIso8601String(),

            // 优惠券详情
            'coupon' => $this->whenLoaded('coupon', function () {
                return new self($this->coupon);
            }),

            // 状态判断
            'isUsed' => $this->status === 'used',
            'isExpired' => $this->expired_at && $this->expired_at->isPast(),
            'isAvailable' => $this->status === 'unused' && (!$this->expired_at || $this->expired_at->isFuture()),

            'createdAt' => $this->created_at?->toIso8601String(),
            'createdAtHuman' => $this->created_at?->diffForHumans(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}