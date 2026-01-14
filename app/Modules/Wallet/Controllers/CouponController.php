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

namespace App\Modules\Wallet\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Wallet\Models\UserCoupon;
use App\Modules\Wallet\Requests\CouponRequest;
use App\Modules\Wallet\Resources\CouponResource;
use App\Modules\Wallet\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 优惠券模块
 *
 * 优惠券控制器
 * 所有返回数据使用Resource格式化为驼峰格式
 */
class CouponController extends Controller
{
    protected $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
    }

    /**
     * 创建优惠券（管理员功能）.
     *
     * @authenticated
     */
    public function create(CouponRequest $request): JsonResponse
    {
        // 检查管理员权限
        if (! $request->user()->hasRole('admin')) {
            return response()->json([
                'code' => 403,
                'message' => '权限不足',
                'data' => null,
            ], 403);
        }

        $data = $request->validated();
        $coupon = $this->couponService->createCoupon($data);

        return response()->json([
            'code' => 200,
            'message' => '优惠券创建成功',
            'data' => new CouponResource($coupon),
        ], 200);
    }

    /**
     * 领取优惠券.
     *
     * @authenticated
     */
    public function issue(CouponRequest $request): JsonResponse
    {
        $data = $request->validated();

        $userCoupon = $this->couponService->issueCoupon(
            $request->user()->id,
            $data['coupon_id']
        );

        return response()->json([
            'code' => 200,
            'message' => '领取成功',
            'data' => CouponResource::userCoupon($userCoupon->load('coupon')),
        ], 200);
    }

    /**
     * 使用优惠券.
     *
     * @authenticated
     */
    public function use(CouponRequest $request): JsonResponse
    {
        $data = $request->validated();

        $userCoupon = $this->couponService->useCoupon(
            $request->user()->id,
            $data['coupon_id'],
            $data['order_amount'] ?? 0
        );

        return response()->json([
            'code' => 200,
            'message' => '优惠券使用成功',
            'data' => CouponResource::userCoupon($userCoupon->load('coupon')),
        ], 200);
    }

    /**
     * 获取用户的优惠券列表.
     *
     * @authenticated
     */
    public function list(Request $request): JsonResponse
    {
        $status = $request->input('status'); // unused/used/expired

        $query = UserCoupon::where('user_id', $request->user()->id)
            ->with('coupon');

        if ($status) {
            $query->where('status', $status);
        }

        $coupons = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => CouponResource::userCoupon($coupons),
                'total' => $coupons->count(),
                'limit' => 20,
            ],
        ], 200);
    }

    /**
     * 获取用户可用的优惠券.
     *
     * @authenticated
     */
    public function usable(Request $request): JsonResponse
    {
        $orderAmount = $request->input('order_amount', 0);

        $coupons = $this->couponService->getUsableCoupons(
            $request->user()->id,
            $orderAmount
        );

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => CouponResource::userCoupon($coupons),
                'total' => $coupons->count(),
            ],
        ], 200);
    }
}
