<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade')->comment('发送者ID');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade')->comment('接收者ID');
            $table->string('content_type')->comment('内容类型：post, comment, message等');
            $table->unsignedBigInteger('content_id')->comment('内容ID');
            $table->string('username')->comment('接收者用户名');
            $table->string('nickname_at_time')->comment('接收者昵称（@时）');
            $table->integer('position')->default(0)->comment('@在内容中的位置');
            $table->enum('status', ['unread', 'read'])->default('unread')->comment('读取状态');
            $table->json('metadata')->nullable()->comment('扩展数据');
            $table->timestamp('read_at')->nullable()->comment('读取时间');
            $table->timestamps();

            // 索引
            $table->index(['sender_id', 'receiver_id'], 'idx_sender_receiver');
            $table->index(['content_type', 'content_id'], 'idx_content');
            $table->index(['receiver_id', 'status'], 'idx_receiver_status');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_mentions');
    }
};
