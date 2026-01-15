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
        Schema::create('orders', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('order_no', 64)->unique()->comment('本地订单号');
            $table->unsignedBigInteger('user_id')->default(0)->comment('用户ID');
            $table->unsignedBigInteger('product_id')->nullable()->comment('商品ID（关联products表）');
            $table->integer('quantity')->default(1)->comment('购买数量');
            $table->decimal('amount', 10, 2)->comment('订单金额');
            $table->string('status', 20)->default('pending')->comment('订单状态（pending/paid/closed）');
            $table->string('pay_type', 20)->nullable()->comment('支付方式（alipay/wechat等）');
            $table->string('payment_no', 64)->nullable()->comment('第三方支付流水号');
            $table->string('payment_status', 20)->nullable()->comment('支付状态（success/failed/error）');
            $table->text('payment_details')->nullable()->comment('支付详情JSON');
            $table->string('payment_error_msg')->nullable()->comment('支付错误信息');
            $table->dateTime('paid_at')->nullable()->comment('支付时间');
            $table->string('subject', 128)->nullable()->comment('订单标题');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->timestamps();

            // 注意：不使用外键约束，避免迁移顺序问题
            // 数据完整性由应用层保证

            // 索引
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
