<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('plug_vote_votes', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->comment('投票标题');
            $table->text('description')->nullable()->comment('投票描述');
            $table->string('type', 20)->default('single')->comment('投票类型：single(单选)/multiple(多选)');
            $table->json('options')->comment('投票选项JSON');
            $table->unsignedBigInteger('user_id')->comment('创建者ID');
            $table->timestamp('start_time')->nullable()->comment('开始时间');
            $table->timestamp('end_time')->nullable()->comment('结束时间');
            $table->boolean('is_active')->default(true)->comment('是否激活');
            $table->boolean('allow_guest')->default(false)->comment('是否允许游客投票');
            $table->boolean('show_results')->default(true)->comment('是否显示结果');
            $table->boolean('require_login')->default(true)->comment('是否需要登录');
            $table->integer('max_votes')->default(1)->comment('最多可投选项数');
            $table->integer('total_votes')->default(0)->comment('总投票数');
            $table->integer('total_participants')->default(0)->comment('总参与人数');
            $table->json('settings')->nullable()->comment('其他设置');
            $table->timestamps();

            $table->index('user_id');
            $table->index(['is_active', 'start_time', 'end_time']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plug_vote_votes');
    }
};
