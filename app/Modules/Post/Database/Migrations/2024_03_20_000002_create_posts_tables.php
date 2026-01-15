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
        // 动态表
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable()->comment('动态标题');
            $table->text('content')->comment('动态内容');
            $table->string('type')->default('post')->comment('内容类型：post-动态，article-文章，question-提问，thread-帖子');
            $table->foreignId('location_id')->nullable()->comment('位置ID');
            $table->tinyInteger('visibility')->default(1)->comment('可见性：1-公开 2-粉丝可见 3-好友可见 4-仅自己可见');
            $table->integer('like_count')->default(0)->comment('点赞数');
            $table->integer('collect_count')->default(0)->comment('收藏数');
            $table->integer('comment_count')->default(0)->comment('评论数');
            $table->integer('share_count')->default(0)->comment('分享数');
            $table->integer('view_count')->default(0)->comment('浏览数');
            $table->boolean('is_top')->default(false)->comment('是否置顶');
            $table->boolean('is_hot')->default(false)->comment('是否热门');
            $table->boolean('is_recommend')->default(false)->comment('是否推荐');
            $table->boolean('is_essence')->default(false)->comment('是否精华');
            $table->tinyInteger('status')->default(1)->comment('状态：1-正常 2-禁用');
            $table->string('ip')->nullable()->comment('发布IP');
            // IP地理位置信息
            $table->string('ip_country')->nullable()->comment('IP国家');
            $table->string('ip_region')->nullable()->comment('IP省份');
            $table->string('ip_city')->nullable()->comment('IP城市');
            $table->string('ip_isp')->nullable()->comment('IP运营商');
            $table->string('ip_location')->nullable()->comment('IP位置（省份-城市）');
            $table->text('user_agent')->nullable()->comment('用户代理');
            $table->string('device')->nullable()->comment('发布设备');
            $table->unsignedBigInteger('repost_id')->nullable()->comment('转发的原动态ID');
            $table->unsignedInteger('repost_count')->default(0)->comment('被转发次数');
            $table->timestamp('published_at')->nullable()->comment('发布时间');
            $table->timestamp('audited_at')->nullable()->comment('审核时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('visibility');
            $table->index('is_top');
            $table->index('is_hot');
            $table->index('is_recommend');
            $table->index('is_essence');
            $table->index('status');
            $table->index('repost_id');
            $table->index(['status', 'published_at']);
            $table->index(['status', 'deleted_at', 'created_at'], 'idx_status_deleted_created');

            // IP字段索引
            $table->index('ip_location', 'idx_ip_location');
            $table->index('ip_country', 'idx_ip_country');
            $table->index('ip_region', 'idx_ip_region');
        });

        // 添加外键约束
        Schema::table('posts', function (Blueprint $table): void {
            $table->foreign('repost_id')
                ->references('id')
                ->on('posts')
                ->onDelete('cascade');
            
            // locations 表已在之前创建，直接添加外键约束
            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
