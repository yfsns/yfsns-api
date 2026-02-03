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
        Schema::create('tencent_sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_id', 50)->unique()->comment('腾讯云模板ID');
            $table->string('template_name', 100)->nullable()->comment('模板名称');
            $table->text('template_content')->comment('模板内容');
            $table->enum('audit_status', ['pending', 'approved', 'rejected'])->default('pending')->comment('审核状态');
            $table->boolean('international')->default(false)->comment('是否国际模板');
            $table->tinyInteger('status')->default(1)->comment('状态：0禁用，1启用');
            $table->json('platform_data')->nullable()->comment('腾讯云原始数据');
            $table->timestamps();

            $table->index('audit_status');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tencent_sms_templates');
    }
};
