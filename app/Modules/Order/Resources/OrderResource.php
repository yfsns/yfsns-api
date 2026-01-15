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

namespace App\Modules\Order\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 订单资源类.
 *
 * 格式化订单数据为驼峰格式
 */
class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (string) $this->id,
            'orderNo' => $this->order_no,
            'userId' => (string) $this->user_id,
            'quantity' => $this->quantity,
            'amount' => (float) $this->amount,

            // 状态信息
            'status' => $this->status,
            'statusText' => $this->status_text,
            'paymentStatus' => $this->payment_status,
            'paymentStatusText' => $this->payment_status_text,

            // 支付信息
            'payType' => $this->pay_type,
            'paymentNo' => $this->payment_no,
            'paidAt' => $this->paid_at?->toIso8601String(),
            'paidAtHuman' => $this->paid_at?->diffForHumans(),

            // 订单信息
            'subject' => $this->subject,
            'remark' => $this->remark,
            'paymentDetails' => $this->payment_details,
            'paymentErrorMsg' => $this->payment_error_msg,

            // 关联数据
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => (string) $this->user->id,
                    'username' => $this->user->username,
                    'nickname' => $this->user->nickname,
                    'avatarUrl' => $this->user->avatar_url,
                ];
            }),

            // 操作权限
            'canCancel' => $this->canCancel(),
            'canRefund' => $this->canRefund(),
            'isPaid' => $this->isPaid(),

            // 时间字段
            'createdAt' => $this->created_at?->toIso8601String(),
            'createdAtHuman' => $this->created_at?->diffForHumans(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
