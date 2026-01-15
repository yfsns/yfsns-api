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
        // 系统配置表
        Schema::create('configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique()->comment('配置键名');
            $table->text('value')->nullable()->comment('配置值');
            $table->string('type')->default('string')->comment('值类型：string/number/boolean/json/array');
            $table->string('group')->default('system')->comment('配置分组');
            $table->string('description')->nullable()->comment('配置描述');
            $table->boolean('is_system')->default(false)->comment('是否系统配置');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configs');
    }
};
