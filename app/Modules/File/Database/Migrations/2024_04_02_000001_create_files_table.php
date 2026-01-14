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
        Schema::create('files', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->comment('文件名');
            $table->string('original_name')->nullable()->comment('原始文件名');
            $table->string('path')->nullable()->comment('文件路径');
            $table->string('url')->nullable()->comment('文件URL（已废弃，现在使用 path 动态生成）');
            $table->unsignedBigInteger('size')->comment('文件大小（字节）');
            $table->string('mime_type')->comment('MIME类型');
            $table->string('type')->comment('文件类型：image,document,video,audio,other');
            $table->string('storage')->comment('存储位置：local,oss,cos,qiniu');

            // 模块关联字段
            $table->string('module')->nullable()->comment('所属模块：post,post,article,user等');
            $table->unsignedBigInteger('module_id')->nullable()->comment('模块记录ID');

            // 文件属性
            $table->string('thumbnail')->nullable()->comment('缩略图/封面（视频封面URL/图片缩略图路径）');
            $table->integer('duration')->nullable()->comment('时长(秒，音视频)');
            $table->integer('width')->nullable()->comment('宽度(图片/视频)');
            $table->integer('height')->nullable()->comment('高度(图片/视频)');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('extra')->nullable()->comment('额外信息');

            // 用户关联
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null')->comment('上传用户ID');
            $table->tinyInteger('status')->default(1)->comment('状态：1=正常,0=禁用');

            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index(['module', 'module_id']);
            $table->index('type');
            $table->index('storage');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
