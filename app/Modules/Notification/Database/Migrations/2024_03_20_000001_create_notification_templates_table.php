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
        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique()->comment('模板代码');
            $table->string('name', 100)->comment('模板名称');
            $table->string('category', 50)->default('general')->comment('模板分类：general(通用),user(用户),order(订单),security(安全)');
            $table->json('channels')->comment('支持的通道：database(数据库),mail(邮件),sms(短信)');
            $table->json('content')->comment('模板内容');
            $table->json('variables')->comment('模板变量');
            $table->tinyInteger('status')->default(1)->comment('状态：0禁用，1启用');
            $table->tinyInteger('priority')->default(1)->comment('优先级：1-低,2-中,3-高');
            $table->text('remark')->nullable()->comment('模板备注');
            $table->string('sms_template_id', 100)->nullable()->comment('SMS模板ID');
            $table->timestamps();

            $table->index('category');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
