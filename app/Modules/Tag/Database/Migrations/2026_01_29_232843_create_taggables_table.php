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
        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tag_id');
            $table->morphs('taggable'); // 创建 taggable_id 和 taggable_type 字段及索引
            $table->unsignedBigInteger('user_id')->comment('添加标签的用户ID');
            $table->timestamps();

            $table->unique(['tag_id', 'taggable_id', 'taggable_type'], 'unique_tag_taggable');
            $table->index('user_id');

            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
