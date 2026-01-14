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

namespace App\Modules\Wallet\Services;

use App\Modules\Wallet\Exceptions\CouponNotUsableException;
use App\Modules\Wallet\Models\Coupon;
use App\Modules\Wallet\Models\UserCoupon;
use Illuminate\Support\Facades\DB;

/**
 * 优惠券服务类
 *
 * 管理优惠券的创建、发放、使用和查询等完整生命周期
 */
class CouponService
{
    /**
     * 创建优惠券.
     *
     * @param array $data 优惠券数据
     */
    public function createCoupon(array $data): Coupon
    {
        return Coupon::create($data);
    }

    /**
     * 发放优惠券给用户.
     *
     * @param int $userId   用户ID
     * @param int $couponId 优惠券ID
     *
     * @throws CouponNotUsableException 优惠券不可用异常
     */
    public function issueCoupon($userId, $couponId): UserCoupon
    {
        return DB::transaction(function () use ($userId, $couponId) {
            $coupon = Coupon::findOrFail($couponId);

            if (! $coupon->isActive()) {
                throw new CouponNotUsableException();
            }

            return UserCoupon::create([
                'user_id' => $userId,
                'coupon_id' => $couponId,
                'status' => 'unused',
            ]);
        });
    }

    /**
     * 使用优惠券.
     *
     * @param int $userId   用户ID
     * @param int $couponId 优惠券ID
     *
     * @throws CouponNotUsableException 优惠券不可用异常
     */
    public function useCoupon($userId, $couponId): UserCoupon
    {
        return DB::transaction(function () use ($userId, $couponId) {
            $userCoupon = UserCoupon::where('user_id', $userId)
                ->where('coupon_id', $couponId)
                ->firstOrFail();

            $userCoupon->use();

            return $userCoupon;
        });
    }

    /**
     * 获取用户的所有优惠券.
     *
     * @param int $userId 用户ID
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserCoupons($userId)
    {
        return UserCoupon::with('coupon')
            ->where('user_id', $userId)
            ->get();
    }

    /**
     * 获取用户可用的优惠券.
     *
     * @param int $userId 用户ID
     *
     * @return \Illuminate\Support\Collection
     */
    public function getUsableCoupons($userId)
    {
        return UserCoupon::with('coupon')
            ->where('user_id', $userId)
            ->where('status', 'unused')
            ->get()
            ->filter(function ($userCoupon) {
                return $userCoupon->isUsable();
            });
    }
}
