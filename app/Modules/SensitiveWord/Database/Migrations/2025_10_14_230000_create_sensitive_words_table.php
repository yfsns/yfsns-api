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
        Schema::create('sensitive_words', function (Blueprint $table): void {
            $table->id();
            $table->string('word', 100)->comment('敏感词');
            $table->string('category', 50)->default('other')->comment('分类：political/pornographic/violence/advertising/other');
            $table->enum('level', ['low', 'medium', 'high'])->default('medium')->comment('敏感级别：低/中/高');
            $table->enum('action', ['replace', 'reject', 'review'])->default('replace')->comment('处理方式：替换/拒绝/待审核');
            $table->string('replacement', 100)->nullable()->comment('替换内容（为空则替换为***）');
            $table->boolean('is_regex')->default(false)->comment('是否正则表达式');
            $table->integer('hit_count')->default(0)->comment('命中次数');
            $table->boolean('status')->default(true)->comment('启用状态');
            $table->text('description')->nullable()->comment('备注说明');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建人');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('更新人');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'status']);
            $table->index('level');
            $table->index('status');
            $table->unique('word');
        });

        // 敏感词命中日志表
        Schema::create('sensitive_word_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sensitive_word_id')->comment('敏感词ID');
            $table->string('content_type', 50)->comment('内容类型：post/comment/thread/nickname');
            $table->unsignedBigInteger('content_id')->nullable()->comment('内容ID');
            $table->unsignedBigInteger('user_id')->nullable()->comment('用户ID');
            $table->text('original_content')->comment('原始内容');
            $table->text('filtered_content')->nullable()->comment('过滤后内容');
            $table->string('action', 20)->comment('执行的动作：replace/reject/review');
            $table->string('ip', 45)->nullable()->comment('IP地址');
            $table->timestamps();

            $table->index(['sensitive_word_id', 'created_at']);
            $table->index(['content_type', 'content_id']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensitive_word_logs');
        Schema::dropIfExists('sensitive_words');
    }
};
