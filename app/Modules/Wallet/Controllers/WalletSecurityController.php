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
use App\Modules\Wallet\Models\WalletSecurity;
use App\Modules\Wallet\Models\WalletSecurityLog;
use App\Modules\Wallet\Requests\SecurityRequest;
use Illuminate\Http\Request;

/**
 * @group 钱包安全模块
 *
 * 钱包安全控制器
 *
 * 主要功能：
 * 1. 管理钱包基本安全设置
 * 2. 处理支付密码验证
 * 3. 管理基本限额控制
 */
class WalletSecurityController extends Controller
{
    /**
     * 获取钱包安全设置.
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSecurity(Request $request)
    {
        $security = WalletSecurity::firstOrCreate(['user_id' => $request->user()->id]);

        return response()->json([
            'data' => $security,
            'status' => $security->status,
        ]);
    }

    /**
     * 更新钱包安全设置.
     *
     * @authenticated
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSecurity(SecurityRequest $request)
    {
        $data = $request->validated();

        $security = WalletSecurity::firstOrCreate(['user_id' => $request->user()->id]);

        $security->update($data);

        return response()->json([
            'message' => '安全设置更新成功',
            'data' => $security,
        ]);
    }

    /**
     * 设置支付密码
     *
     * @authenticated
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setPaymentPassword(SecurityRequest $request)
    {
        $data = $request->validated();

        $security = WalletSecurity::firstOrCreate(['user_id' => $request->user()->id]);
        $security->setPaymentPassword($data['password']);

        return response()->json([
            'message' => '支付密码设置成功',
        ]);
    }

    /**
     * 验证支付密码
     *
     * @authenticated
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPaymentPassword(SecurityRequest $request)
    {
        $data = $request->validated();

        $security = WalletSecurity::firstOrCreate(['user_id' => $request->user()->id]);

        if (! $security->password_enabled) {
            return response()->json([
                'message' => '支付密码未启用',
                'verified' => true,
            ]);
        }

        if ($security->verifyPaymentPassword($data['password'])) {
            return response()->json([
                'message' => '密码验证成功',
                'verified' => true,
            ]);
        }

        // 记录密码验证失败
        WalletSecurityLog::logSecurityEvent(
            $request->user()->id,
            'password_failed',
            '支付密码验证失败'
        );

        return response()->json([
            'message' => '密码验证失败',
            'verified' => false,
        ], 400);
    }

    /**
     * 检查交易限额.
     *
     * @authenticated
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkTransactionLimit(SecurityRequest $request)
    {
        $data = $request->validated();

        $security = WalletSecurity::firstOrCreate(['user_id' => $request->user()->id]);
        $period = $data['period'] ?? 'single';

        $isAllowed = $security->checkTransactionLimit($data['amount'], $period);

        if (! $isAllowed) {
            WalletSecurityLog::logSecurityEvent(
                $request->user()->id,
                'limit_exceeded',
                "交易金额 {$data['amount']} 超过{$period}限额"
            );
        }

        return response()->json([
            'allowed' => $isAllowed,
            'amount' => $data['amount'],
            'period' => $period,
        ]);
    }

    /**
     * 获取安全日志.
     *
     * @authenticated
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSecurityLogs(SecurityRequest $request)
    {
        $data = $request->validated();

        $limit = $data['limit'] ?? 20;

        $logs = WalletSecurityLog::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $logs,
            'total' => $logs->count(),
        ]);
    }

    /**
     * 暂停钱包.
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function suspendWallet(Request $request)
    {
        $security = WalletSecurity::firstOrCreate(['user_id' => $request->user()->id]);
        $security->suspend();

        WalletSecurityLog::logSecurityEvent(
            $request->user()->id,
            'freeze',
            '用户主动暂停钱包'
        );

        return response()->json([
            'message' => '钱包已暂停',
        ]);
    }

    /**
     * 激活钱包.
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function activateWallet(Request $request)
    {
        $security = WalletSecurity::firstOrCreate(['user_id' => $request->user()->id]);
        $security->activate();

        WalletSecurityLog::logSecurityEvent(
            $request->user()->id,
            'unfreeze',
            '用户激活钱包'
        );

        return response()->json([
            'message' => '钱包已激活',
        ]);
    }
}
