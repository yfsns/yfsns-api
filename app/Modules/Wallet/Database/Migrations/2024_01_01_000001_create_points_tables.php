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
        // 1. 先创建积分规则表（因为 wallet_point_records 依赖它）
        Schema::create('wallet_points_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->comment('规则名称');
            $table->string('code')->unique()->comment('规则代码');
            $table->text('description')->nullable()->comment('规则描述');
            $table->enum('trigger_type', ['daily', 'once', 'action'])->default('action')->comment('触发类型');
            $table->string('action')->nullable()->comment('触发动作');
            $table->integer('points')->default(0)->comment('积分数量');
            $table->enum('points_type', ['fixed', 'dynamic'])->default('fixed')->comment('积分类型');
            $table->json('formula')->nullable()->comment('动态计算公式');
            $table->integer('max_times')->default(0)->comment('最大触发次数');
            $table->integer('daily_limit')->default(0)->comment('每日限制次数');
            $table->json('conditions')->nullable()->comment('触发条件');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('状态');
            $table->timestamp('start_time')->nullable()->comment('开始时间');
            $table->timestamp('end_time')->nullable()->comment('结束时间');
            $table->integer('priority')->default(0)->comment('优先级');
            $table->timestamps();

            $table->index(['status', 'action']);
            $table->index(['start_time', 'end_time']);
            $table->index('priority');
        });

        // 2. 创建积分表
        Schema::create('wallet_points', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->integer('balance')->default(0);
            $table->integer('version')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 3. 最后创建积分记录表（依赖 users 和 wallet_points_rules）
        Schema::create('wallet_point_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('points_rule_id')->nullable()->comment('积分规则ID');
            $table->string('type'); // earn, use, expire, adjust
            $table->integer('amount'); // 正数表示获得，负数表示使用
            $table->string('description');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('points_rule_id')->references('id')->on('wallet_points_rules')->onDelete('set null');
            $table->index(['user_id', 'type']);
            $table->index(['points_rule_id', 'created_at']); // 指向 wallet_points_rules 表
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_point_records');
        Schema::dropIfExists('wallet_points_rules');
        Schema::dropIfExists('wallet_points');
    }
};
