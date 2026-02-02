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
        Schema::create('categorizables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->morphs('categorizable'); // 创建 categorizable_id 和 categorizable_type 字段
            $table->unsignedBigInteger('user_id')->comment('添加分类的用户ID');
            $table->timestamps();

            $table->unique(['category_id', 'categorizable_id', 'categorizable_type'], 'unique_category_categorizable');
            $table->index(['categorizable_type', 'categorizable_id']);
            $table->index('user_id');

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorizables');
    }
};
