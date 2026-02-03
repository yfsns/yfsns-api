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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('分类名称');
            $table->string('slug')->unique()->comment('分类别名，用于URL');
            $table->text('description')->nullable()->comment('分类描述');
            $table->string('icon')->nullable()->comment('分类图标');
            $table->string('color', 7)->default('#3498db')->comment('分类颜色');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('父分类ID');
            $table->integer('sort_order')->default(0)->comment('排序权重');
            $table->boolean('is_active')->default(true)->comment('是否激活');
            $table->boolean('is_system')->default(false)->comment('是否系统分类');
            $table->json('metadata')->nullable()->comment('扩展元数据');
            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
            $table->index(['is_active', 'is_system']);
            $table->index('slug');

            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
