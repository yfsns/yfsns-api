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
        // 用户表
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->unique()->comment('用户名');
            $table->string('email')->nullable()->unique()->comment('邮箱');
            $table->string('password')->nullable()->comment('密码');
            $table->string('nickname')->nullable()->comment('昵称');
            $table->string('avatar')->nullable()->comment('头像路径');
            $table->string('phone')->nullable()->unique()->comment('手机号');
            $table->string('wechat_openid')->nullable()->unique()->comment('微信OpenID');
            $table->string('wechat_unionid')->nullable()->unique()->comment('微信UnionID');
            $table->tinyInteger('gender')->default(0)->comment('性别 0-保密 1-男 2-女');
            $table->date('birthday')->nullable()->comment('生日');
            $table->text('bio')->nullable()->comment('个人简介');
            $table->json('settings')->nullable()->comment('用户设置');
            $table->boolean('is_admin')->default(false)->comment('是否是管理员：0-否 1-是');
            $table->string('admin_remark')->nullable()->comment('管理员备注');
            $table->foreignId('role_id')->default(2)->constrained('user_roles')->comment('用户角色ID');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
            $table->string('last_login_ip')->nullable()->comment('最后登录IP');
            $table->timestamp('email_verified_at')->nullable()->comment('邮箱验证时间');
            $table->timestamp('phone_verified_at')->nullable()->comment('手机验证时间');
            $table->string('register_ip')->nullable()->comment('注册IP地址');
            $table->string('register_ip_location')->nullable()->comment('注册IP位置');
            $table->string('last_login_ip_location')->nullable()->comment('最后登录IP位置');
            $table->tinyInteger('status')->default(1)->comment('用户状态：0-禁用 1-启用');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // 添加索引
            $table->index('is_admin', 'idx_is_admin');
            $table->index('role_id', 'idx_role_id');
            $table->index('register_ip', 'idx_register_ip');
            $table->index('last_login_ip', 'idx_last_login_ip');
            // 添加复合索引：status + created_at，用于推荐用户查询优化
            $table->index(['status', 'created_at'], 'idx_users_status_created_at');
            // 添加单独的状态索引
            $table->index('status', 'idx_users_status');
        });

        // 用户设置表
        Schema::create('user_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('key')->comment('设置键名');
            $table->text('value')->nullable()->comment('设置值');
            $table->timestamps();
            $table->unique(['user_id', 'key']);
        });

        // 用户关注表
        Schema::create('user_follows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('following_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('status')->default(1)->comment('关注状态：1-正常 0-已取消');
            $table->timestamps();
            $table->unique(['follower_id', 'following_id']);
            // 添加索引
            $table->index(['follower_id', 'status']);
            $table->index(['following_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_follows');
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('users');
    }
};
