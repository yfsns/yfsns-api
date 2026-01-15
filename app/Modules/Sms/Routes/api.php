<?php

/*
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

use App\Modules\Sms\Controllers\SmsController;
use Illuminate\Support\Facades\Route;

// SMS模块只提供短信发送能力，验证码相关接口请使用认证模块
// 验证码发送：POST /api/v1/auth/register/verification/sms (注册) 或 POST /api/v1/auth/sms/verification (登录)
// 验证码验证：在注册/登录接口中自动验证

// 获取可用通道列表
Route::get('/channels', [SmsController::class, 'getChannels']);

// 发送短信（支持指定通道）
Route::post('/send', [SmsController::class, 'send']);

// 发送通知短信
Route::post('/send-notification', [SmsController::class, 'sendNotification']);

// 发送验证码短信
Route::post('/send-verification', [SmsController::class, 'sendVerification']);

// 批量发送短信
Route::post('/send-batch', [SmsController::class, 'sendBatch']);
