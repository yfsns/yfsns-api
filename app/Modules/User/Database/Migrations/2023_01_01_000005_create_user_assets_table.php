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
        Schema::create('user_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type')->comment('资源类型：avatar=头像,background=背景图,album=相册,other=其他');
            $table->string('name')->comment('资源名称');
            $table->string('path')->comment('存储路径');
            $table->string('url')->comment('访问URL');
            $table->string('mime_type')->comment('MIME类型');
            $table->unsignedBigInteger('size')->comment('文件大小（字节）');
            $table->integer('width')->nullable()->comment('宽度（图片/视频）');
            $table->integer('height')->nullable()->comment('高度（图片/视频）');
            $table->integer('duration')->nullable()->comment('时长（视频/音频）');
            $table->string('thumbnail')->nullable()->comment('缩略图URL');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('额外信息');
            $table->tinyInteger('status')->default(1)->comment('状态：1=正常,0=禁用');

            // 审核相关字段
            $table->string('review_status')->default('pending')->comment('审核状态：pending=待审核,approved=已通过,rejected=已拒绝');
            $table->text('review_remark')->nullable()->comment('审核备注');
            $table->unsignedBigInteger('reviewer_id')->nullable()->comment('审核员ID');
            $table->timestamp('reviewed_at')->nullable()->comment('审核时间');
            $table->timestamp('review_expires_at')->nullable()->comment('审核过期时间');
            $table->integer('review_attempts')->default(0)->comment('审核尝试次数');

            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index(['user_id', 'type']);
            $table->index('status');
            $table->index('review_status');
            $table->index('review_expires_at');

            // 外键约束
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_assets');
    }
};
