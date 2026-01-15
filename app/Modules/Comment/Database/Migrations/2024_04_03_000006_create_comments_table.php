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
        // 创建评论表
        Schema::create('comments', function (Blueprint $table): void {
            $table->id()->comment('评论ID');
            $table->unsignedBigInteger('user_id')->comment('评论用户ID');
            $table->unsignedBigInteger('target_id')->comment('目标ID');
            $table->string('target_type')->comment('目标类型');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('父评论ID，用于回复功能');
            $table->text('content')->comment('评论内容');
            $table->string('content_type')->default('text')->comment('内容类型：text-文本，image-图片，video-视频');
            $table->string('video_url')->nullable()->comment('视频URL');
            $table->json('images')->nullable()->comment('评论图片，JSON格式存储图片URL数组');
            $table->integer('like_count')->default(0)->comment('点赞数');
            $table->integer('reply_count')->default(0)->comment('回复数');
            $table->integer('hot_score')->default(0)->comment('热门分数，用于热门排序');
            $table->tinyInteger('status')->default(1)->comment('状态：1-正常 2-删除 3-待审核 4-已屏蔽');
            $table->timestamp('published_at')->nullable()->comment('发布时间');
            $table->timestamp('audited_at')->nullable()->comment('审核时间');
            $table->string('ip')->nullable()->comment('评论IP地址');
            $table->string('ip_country')->nullable()->comment('IP国家');
            $table->string('ip_region')->nullable()->comment('IP省份');
            $table->string('ip_city')->nullable()->comment('IP城市');
            $table->string('ip_isp')->nullable()->comment('IP运营商');
            $table->string('ip_location')->nullable()->comment('IP位置（省份-城市）');
            $table->text('user_agent')->nullable()->comment('用户代理');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
            $table->softDeletes()->comment('软删除时间');

            // 添加索引
            $table->index('user_id', 'idx_user_id');
            $table->index(['target_id', 'target_type'], 'idx_target');
            $table->index('parent_id', 'idx_parent_id');
            $table->index('status', 'idx_status');
            $table->index(['status', 'published_at'], 'idx_status_published_at');
            $table->index('ip', 'idx_comment_ip');
            $table->index('ip_location', 'idx_comment_ip_location');
            $table->index('hot_score', 'idx_hot_score');
        });

        // 创建评论关系表
        Schema::create('comment_relations', function (Blueprint $table): void {
            $table->id()->comment('关系ID');
            $table->unsignedBigInteger('ancestor')->comment('祖先评论ID');
            $table->unsignedBigInteger('descendant')->comment('后代评论ID');
            $table->integer('depth')->default(0)->comment('关系深度，表示祖先到后代的层级数');
            $table->string('path')->comment('关系路径，格式如：1,2,3 表示评论的层级关系');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');

            // 添加唯一索引
            $table->unique(['ancestor', 'descendant'], 'uk_ancestor_descendant');

            // 添加索引
            $table->index('depth', 'idx_depth');
            $table->index('path', 'idx_path');
        });

    }

    public function down(): void
    {
        // 注意删除顺序，先删除有外键依赖的表
        Schema::dropIfExists('comment_relations');
        Schema::dropIfExists('comments');
    }
};
