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
        Schema::create('search_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('query', 255)->comment('搜索关键词');
            $table->string('type', 50)->nullable()->comment('搜索类型');
            $table->json('filters')->nullable()->comment('过滤条件');
            $table->integer('results_count')->default(0)->comment('结果数量');
            $table->string('ip_address', 45)->nullable()->comment('IP地址');
            $table->string('user_agent')->nullable()->comment('用户代理');
            $table->unsignedBigInteger('user_id')->nullable()->comment('用户ID');
            $table->integer('response_time')->nullable()->comment('响应时间(毫秒)');
            $table->string('status', 20)->default('success')->comment('搜索状态');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->timestamps();

            // 索引
            $table->index(['query', 'type']);
            $table->index(['user_id']);
            $table->index(['created_at']);
            $table->index(['ip_address']);

            // 外键约束
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
