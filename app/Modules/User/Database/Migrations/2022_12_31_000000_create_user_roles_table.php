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
        Schema::create('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->comment('用户角色名称');
            $table->string('key')->unique()->comment('用户角色标识');
            $table->string('description')->nullable()->comment('用户角色描述');
            $table->json('permissions')->nullable()->comment('权限列表');
            $table->boolean('status')->default(true)->comment('状态');
            $table->tinyInteger('type')->default(0)->comment('用户角色类型');
            $table->integer('sort')->default(0)->comment('排序');
            $table->boolean('is_system')->default(false)->comment('是否系统用户角色');
            $table->boolean('is_default')->default(false)->comment('是否默认用户角色');
            $table->timestamps();
            $table->softDeletes();
        });

        // 注意：user_permissions 和 user_role_permissions 表已由其他迁移文件创建
        // 见：2025_01_15_000000_create_user_role_permissions_table.php
    }

    public function down(): void
    {
        // 注意：user_role_permissions 和 user_permissions 表由其他迁移文件管理
        Schema::dropIfExists('user_roles');
    }
};
