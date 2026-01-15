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
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('模板名称');
            $table->string('code')->comment('模板代码，用于标识模板');
            $table->text('content')->comment('模板内容');
            $table->string('driver', 50)->comment('短信驱动：aliyun, tencent等');
            $table->string('template_id')->nullable()->comment('第三方平台的模板ID');
            $table->json('variables')->nullable()->comment('模板变量，JSON数组格式');
            $table->tinyInteger('status')->default(1)->comment('状态：1-启用，0-禁用');
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index(['code', 'driver']);
            $table->index('status');
            $table->unique(['code', 'driver', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};
