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
        // 虚拟币余额表
        Schema::create('wallet_coins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique()->comment('用户ID');
            $table->integer('coins')->default(0)->comment('音符币余额');
            $table->integer('version')->default(1)->comment('版本号（乐观锁）');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('coins');
        });

        // 虚拟币交易记录表
        Schema::create('wallet_coin_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->unsignedBigInteger('target_user_id')->nullable()->comment('目标用户ID（打赏时使用）');
            $table->enum('type', ['recharge', 'tip', 'reward', 'consume'])->comment('交易类型');
            $table->integer('coins')->comment('音符币变动数量');
            $table->decimal('rmb_amount', 10, 2)->comment('对应的人民币金额');
            $table->string('description')->nullable()->comment('交易描述');
            $table->json('metadata')->nullable()->comment('交易元数据');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed')->comment('交易状态');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('target_user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['user_id', 'created_at']);
            $table->index(['target_user_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_coin_transactions');
        Schema::dropIfExists('wallet_coins');
    }
};
