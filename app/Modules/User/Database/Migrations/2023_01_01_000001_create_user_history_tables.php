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
    public function up(): void
    {
        // 用户浏览历史表
        Schema::create('user_browsing_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type')->comment('历史类型：post-动态，article-文章，video-视频');
            $table->unsignedBigInteger('target_id')->comment('目标ID');
            $table->json('target_data')->nullable()->comment('目标数据快照');
            $table->timestamp('last_viewed_at')->comment('最后浏览时间');
            $table->timestamps();

            $table->unique(['user_id', 'type', 'target_id']);
            $table->index(['user_id', 'type', 'last_viewed_at']);
        });

        // 用户浏览统计表
        Schema::create('content_browsing_statistics', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->comment('历史类型');
            $table->unsignedBigInteger('target_id')->comment('目标ID');
            $table->integer('view_count')->default(0)->comment('浏览次数');
            $table->integer('unique_view_count')->default(0)->comment('独立浏览次数');
            $table->json('view_stats')->nullable()->comment('浏览统计');
            $table->json('user_stats')->nullable()->comment('用户统计');
            $table->json('time_stats')->nullable()->comment('时间统计');
            $table->timestamps();

            $table->unique(['type', 'target_id']);
            $table->index(['type', 'view_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_browsing_statistics');
        Schema::dropIfExists('user_browsing_histories');
    }
};
