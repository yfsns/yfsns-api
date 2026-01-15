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
        Schema::create('website_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('site_name')->comment('站点名称');
            $table->string('site_url')->nullable()->comment('站点URL');
            $table->text('site_description')->nullable()->comment('站点介绍');
            $table->string('site_keywords')->nullable()->comment('站点关键词');
            $table->string('site_title')->nullable()->comment('站点首页标题');
            $table->string('site_logo')->nullable()->comment('站点Logo');
            $table->string('site_header_logo')->nullable()->comment('首页头部Logo');
            $table->string('site_favicon')->nullable()->comment('站点图标');
            $table->boolean('site_status')->default(true)->comment('站点开关');
            $table->string('icp_number')->nullable()->comment('ICP备案号');
            $table->string('police_record')->nullable()->comment('公安备案号');
            $table->text('statistics_code')->nullable()->comment('统计代码');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_configs');
    }
};
