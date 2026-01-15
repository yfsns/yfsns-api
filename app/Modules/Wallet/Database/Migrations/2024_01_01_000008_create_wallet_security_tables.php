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
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 钱包安全设置表
        Schema::create('wallet_securities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique()->comment('用户ID');
            $table->decimal('daily_limit', 10, 2)->default(0)->comment('每日交易限额（0表示无限制）');
            $table->decimal('single_limit', 10, 2)->default(0)->comment('单笔交易限额（0表示无限制）');
            $table->decimal('monthly_limit', 10, 2)->default(0)->comment('每月交易限额（0表示无限制）');
            $table->boolean('password_enabled')->default(false)->comment('是否启用支付密码');
            $table->string('payment_password')->nullable()->comment('支付密码（加密存储）');
            $table->enum('status', ['active', 'suspended'])->default('active')->comment('钱包状态');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('status');
        });

        // 钱包安全日志表
        Schema::create('wallet_security_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->enum('action', ['freeze', 'unfreeze', 'limit_exceeded', 'password_failed', 'suspicious'])->comment('操作类型');
            $table->string('reason')->comment('操作原因');
            $table->string('ip_address')->nullable()->comment('IP地址');
            $table->text('user_agent')->nullable()->comment('用户代理');
            $table->json('metadata')->nullable()->comment('额外信息（JSON格式）');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_security_logs');
        Schema::dropIfExists('wallet_securities');
    }
};
