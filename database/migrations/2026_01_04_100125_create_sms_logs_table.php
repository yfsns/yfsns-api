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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->comment('手机号');
            $table->text('content')->nullable()->comment('短信内容');
            $table->unsignedBigInteger('template_id')->nullable()->comment('SMS模板ID');
            $table->string('template_code', 100)->nullable()->comment('模板代码');
            $table->json('template_data')->nullable()->comment('发送的数据');
            $table->string('driver', 50)->comment('通道类型（aliyun/tencent等）');
            $table->tinyInteger('status')->default(0)->comment('发送状态（0=失败, 1=成功）');
            $table->text('error_message')->nullable()->comment('错误消息');
            $table->json('response_data')->nullable()->comment('API响应数据');
            $table->string('ip', 45)->nullable()->comment('发送请求的IP地址');
            $table->text('user_agent')->nullable()->comment('用户代理信息');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['phone', 'status']);
            $table->index(['template_code', 'driver']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
