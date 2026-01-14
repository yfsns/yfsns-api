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
        // 优惠券模板表
        Schema::create('wallet_coupon_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->comment('优惠券名称');
            $table->text('description')->nullable()->comment('优惠券描述');
            $table->enum('type', ['discount', 'cash', 'free_shipping'])->comment('优惠券类型');
            $table->decimal('value', 10, 2)->comment('优惠券面值');
            $table->decimal('min_amount', 10, 2)->default(0)->comment('最低消费金额');
            $table->integer('total_quantity')->default(-1)->comment('总发放数量（-1表示无限制）');
            $table->integer('used_quantity')->default(0)->comment('已使用数量');
            $table->date('start_date')->comment('生效开始日期');
            $table->date('end_date')->comment('生效结束日期');
            $table->boolean('is_active')->default(true)->comment('是否激活');
            $table->json('rules')->nullable()->comment('使用规则（JSON格式）');
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });

        // 用户优惠券表
        Schema::create('wallet_coupons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->unsignedBigInteger('coupon_template_id')->comment('优惠券模板ID');
            $table->string('code')->unique()->comment('优惠券码');
            $table->enum('status', ['unused', 'used', 'expired'])->default('unused')->comment('状态');
            $table->timestamp('used_at')->nullable()->comment('使用时间');
            $table->unsignedBigInteger('order_id')->nullable()->comment('使用的订单ID');
            $table->decimal('discount_amount', 10, 2)->nullable()->comment('实际优惠金额');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('coupon_template_id')->references('id')->on('wallet_coupon_templates')->onDelete('cascade');

            $table->index(['user_id', 'status']);
            $table->index(['code', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_coupons');
        Schema::dropIfExists('wallet_coupon_templates');
    }
};
