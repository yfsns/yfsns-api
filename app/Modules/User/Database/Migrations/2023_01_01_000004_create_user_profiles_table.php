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
        Schema::create('user_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('real_name')->nullable()->comment('真实姓名');
            $table->string('id_card')->nullable()->comment('身份证号');
            $table->string('address')->nullable()->comment('居住地址');
            $table->string('education')->nullable()->comment('学历');
            $table->string('occupation')->nullable()->comment('职业');
            $table->string('company')->nullable()->comment('公司');
            $table->string('position')->nullable()->comment('职位');
            $table->decimal('income', 10, 2)->nullable()->comment('年收入');
            $table->string('marital_status')->nullable()->comment('婚姻状况');
            $table->string('height')->nullable()->comment('身高');
            $table->string('weight')->nullable()->comment('体重');
            $table->string('blood_type')->nullable()->comment('血型');
            $table->string('zodiac')->nullable()->comment('星座');
            $table->string('hometown')->nullable()->comment('籍贯');
            $table->json('interests')->nullable()->comment('兴趣爱好');
            $table->json('skills')->nullable()->comment('技能特长');
            $table->text('self_introduction')->nullable()->comment('自我介绍');
            $table->json('social_media')->nullable()->comment('社交媒体账号');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
