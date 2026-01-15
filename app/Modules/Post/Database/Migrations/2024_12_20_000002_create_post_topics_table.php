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
        Schema::create('post_topics', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('post_id')->comment('动态ID');
            $table->unsignedBigInteger('topic_id')->comment('话题ID');
            $table->integer('position')->default(0)->comment('在内容中的位置');
            $table->timestamps();

            // 外键约束
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('topic_id')->references('id')->on('topics')->onDelete('cascade');

            // 唯一约束：同一个动态不能重复关联同一个话题
            $table->unique(['post_id', 'topic_id'], 'unique_post_topic');

            // 索引
            $table->index(['post_id'], 'idx_post_topics_post_id');
            $table->index(['topic_id', 'created_at'], 'idx_topic_usage');
            $table->index(['topic_id', 'post_id'], 'idx_topic_post');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_topics');
    }
};
