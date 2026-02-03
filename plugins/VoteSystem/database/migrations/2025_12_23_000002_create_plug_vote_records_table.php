<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('plug_vote_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('vote_id')->comment('投票ID');
            $table->unsignedBigInteger('user_id')->nullable()->comment('用户ID，游客为null');
            $table->string('ip_address', 45)->nullable()->comment('IP地址');
            $table->string('user_agent')->nullable()->comment('用户代理');
            $table->json('options')->comment('选择的选项ID数组');
            $table->timestamp('voted_at')->comment('投票时间');
            $table->timestamps();

            $table->index(['vote_id', 'user_id']);
            $table->index(['vote_id', 'ip_address']);
            $table->index('voted_at');

            $table->foreign('vote_id')->references('id')->on('plug_vote_votes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plug_vote_records');
    }
};
