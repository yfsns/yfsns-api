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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('标签名称');
            $table->string('slug')->unique()->comment('标签别名，用于URL');
            $table->text('description')->nullable()->comment('标签描述');
            $table->string('color', 7)->default('#3498db')->comment('标签颜色');
            $table->integer('usage_count')->default(0)->comment('使用次数');
            $table->integer('sort_order')->default(0)->comment('排序权重');
            $table->boolean('is_system')->default(false)->comment('是否为系统标签');
            $table->json('metadata')->nullable()->comment('扩展元数据');
            $table->timestamps();

            $table->index(['usage_count', 'sort_order']);
            $table->index('is_system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
