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
        Schema::create('topics', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('cover')->nullable();
            $table->integer('post_count')->default(0);
            $table->integer('follower_count')->default(0);
            $table->tinyInteger('status')->default(0)->comment('0:待审核 1:启用 2:禁用');

            // 添加审计字段
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建者ID');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('更新者ID');
            $table->unsignedBigInteger('deleted_by')->nullable()->comment('删除者ID');

            $table->timestamps();
            $table->softDeletes();

            // 添加外键约束
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            // 添加索引
            $table->index('created_by', 'idx_topics_created_by');
            $table->index('updated_by', 'idx_topics_updated_by');
            // 添加复合索引：status + post_count + follower_count，用于推荐话题查询优化
            $table->index(['status', 'post_count', 'follower_count'], 'idx_topics_status_post_follower');
            // 添加单独的状态索引
            $table->index('status', 'idx_topics_status');
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table): void {
            $table->dropForeign(['created_by', 'updated_by', 'deleted_by']);
            $table->dropIndex('idx_topics_created_by');
            $table->dropIndex('idx_topics_updated_by');
        });

        Schema::dropIfExists('topics');
    }
};
