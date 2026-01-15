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
        Schema::create('email_servers', function (Blueprint $table): void {
            $table->id();
            $table->string('driver', 20)->default('smtp')->comment('邮件驱动：smtp, mailgun, ses, log');

            // SMTP 配置
            $table->string('host')->nullable()->comment('SMTP主机');
            $table->integer('port')->nullable()->comment('SMTP端口');
            $table->string('encryption', 10)->nullable()->comment('加密方式：tls, ssl');
            $table->string('username')->nullable()->comment('SMTP用户名');
            $table->string('password')->nullable()->comment('SMTP密码');

            // API 驱动配置 (Mailgun, SES等)
            $table->string('api_key')->nullable()->comment('API密钥');
            $table->string('domain')->nullable()->comment('域名');
            $table->string('secret')->nullable()->comment('密钥');
            $table->string('region')->nullable()->comment('区域');

            // 发件人配置
            $table->string('from_address')->nullable()->comment('发件人邮箱');
            $table->string('from_name')->nullable()->comment('发件人姓名');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_servers');
    }
};
