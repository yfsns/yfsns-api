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
        Schema::create('review_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('reviewable_type');  // 被审核内容的类型（Article、Post、Thread等）
            $table->unsignedBigInteger('reviewable_id');  // 被审核内容的ID
            $table->string('channel')->default('manual');  // 审核渠道：manual=人工, ai=AI
            $table->unsignedBigInteger('admin_id')->nullable();  // 管理员ID（人工审核时使用）
            $table->string('plugin_name')->nullable();  // 插件名称（AI审核时使用）
            $table->string('previous_status');  // 审核前状态
            $table->string('new_status');  // 审核后状态
            $table->text('remark')->nullable();  // 审核备注
            $table->json('audit_result')->nullable();  // 审核结果详情（JSON，AI审核时使用）
            $table->json('extra_data')->nullable();    // 扩展数据，各模块自定义审核参数
            $table->timestamps();

            // 索引
            $table->index(['reviewable_type', 'reviewable_id']);
            $table->index('channel');
            $table->index('admin_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_logs');
    }
};
