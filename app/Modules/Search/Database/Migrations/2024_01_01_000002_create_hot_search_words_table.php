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
        Schema::create('search_hot_words', function (Blueprint $table): void {
            $table->id();
            $table->string('keyword', 100)->unique()->comment('搜索关键词');
            $table->integer('search_count')->default(0)->comment('搜索次数');
            $table->integer('click_count')->default(0)->comment('点击次数');
            $table->decimal('weight', 5, 2)->default(1.00)->comment('权重');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建者ID');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('更新者ID');
            $table->timestamp('last_searched_at')->nullable()->comment('最后搜索时间');
            $table->timestamps();

            // 索引
            $table->index(['keyword']);
            $table->index(['search_count']);
            $table->index(['weight']);
            $table->index(['is_active']);
            $table->index(['last_searched_at']);

            // 外键约束
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_hot_words');
    }
};
