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

namespace App\Modules\Subscription\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Subscription\Requests\DestroySubscriptionRequest;
use App\Modules\Subscription\Requests\StoreSubscriptionRequest;
use App\Modules\Subscription\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 订阅控制器.
 */
class SubscriptionController extends Controller
{

    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * 获取我的订阅列表.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('perPage', 20), 100);
        $subscriptions = $this->subscriptionService->getUserSubscriptions(
            auth()->user(),
            $perPage
        );

        // ⭐ 使用统一的分页响应格式
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'items' => $subscriptions->items(),
                'pagination' => [
                    'current_page' => $subscriptions->currentPage(),
                    'last_page' => $subscriptions->lastPage(),
                    'per_page' => $subscriptions->perPage(),
                    'total' => $subscriptions->total(),
                ]
            ]
        ]);
    }

    /**
     * 创建订阅（用于测试，实际应该通过支付流程）.
     */
    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $data = $request->validated();

        // 根据类型获取被订阅对象
        $subscribable = $this->getSubscribableModel(
            $data['subscribable_type'],
            $data['subscribable_id']
        );

        if (! $subscribable) {
            return response()->json(['code' => 404, 'message' => '订阅对象不存在', 'data' => null], 200);
        }

        $subscription = $this->subscriptionService->subscribe(
            auth()->user(),
            $subscribable,
            $data['price'] ?? 0,
            $data['days'] ?? 365
        );

        return response()->json(['code' => 200, 'message' => '订阅成功', 'data' => $subscription], 200);
    }

    /**
     * 取消订阅.
     */
    public function destroy(DestroySubscriptionRequest $request): JsonResponse
    {
        $data = $request->validated();

        $subscribable = $this->getSubscribableModel(
            $data['subscribable_type'],
            $data['subscribable_id']
        );

        if (! $subscribable) {
            return response()->json(['code' => 404, 'message' => '订阅对象不存在', 'data' => null], 200);
        }

        $result = $this->subscriptionService->unsubscribe(
            auth()->user(),
            $subscribable
        );

        if ($result) {
            return response()->json(['code' => 200, 'message' => '取消订阅成功', 'data' => null], 200);
        }

        return response()->json(['code' => 404, 'message' => '未找到有效订阅', 'data' => null], 200);
    }

    /**
     * 根据类型获取订阅对象
     */
    protected function getSubscribableModel(string $type, int $id)
    {
        $modelMap = [
            'post' => \App\Modules\Post\Models\Post::class,
            // 未来可以添加更多类型
            // 'course' => \App\Modules\Course\Models\Course::class,
            // 'video' => \App\Modules\Video\Models\VideoSeries::class,
        ];

        if (! isset($modelMap[$type])) {
            return null;
        }

        return $modelMap[$type]::find($id);
    }
}
