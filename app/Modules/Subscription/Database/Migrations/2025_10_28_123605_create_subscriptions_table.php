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
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('订阅者ID');
            $table->string('subscribable_type')->comment('被订阅对象类型');
            $table->unsignedBigInteger('subscribable_id')->comment('被订阅对象ID');
            $table->unsignedBigInteger('order_id')->nullable()->comment('关联订单ID');
            $table->decimal('price', 10, 2)->default(0)->comment('订阅价格');
            $table->timestamp('started_at')->nullable()->comment('开始时间');
            $table->timestamp('expired_at')->nullable()->comment('过期时间');
            $table->string('status', 20)->default('active')->comment('订阅状态: active, expired, cancelled');
            $table->timestamps();

            // 索引
            $table->index(['user_id', 'subscribable_type', 'subscribable_id'], 'subscriptions_user_subscribable');
            $table->index(['subscribable_type', 'subscribable_id'], 'subscriptions_subscribable');
            $table->index(['user_id', 'status'], 'subscriptions_user_status');
            $table->index('expired_at');

            // 外键约束
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
