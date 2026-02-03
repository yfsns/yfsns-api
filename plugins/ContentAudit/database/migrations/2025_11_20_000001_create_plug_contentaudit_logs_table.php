<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('plug_contentaudit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('content_id')->comment('内容ID');
            $table->string('content_type', 20)->comment('内容类型：article/post');
            $table->string('status', 20)->default('pending')->comment('审核状态：pending/pass/reject');
            $table->text('result')->nullable()->comment('审核结果（JSON）');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->timestamps();

            $table->index(['content_id', 'content_type']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plug_contentaudit_logs');
    }
};
