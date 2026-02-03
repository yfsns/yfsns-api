<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('plug_vote_options', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('vote_id')->comment('投票ID');
            $table->string('title')->comment('选项标题');
            $table->text('description')->nullable()->comment('选项描述');
            $table->string('image')->nullable()->comment('选项图片');
            $table->integer('votes_count')->default(0)->comment('投票数');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->boolean('is_active')->default(true)->comment('是否激活');
            $table->timestamps();

            $table->index(['vote_id', 'is_active']);
            $table->index(['vote_id', 'sort_order']);

            $table->foreign('vote_id')->references('id')->on('plug_vote_votes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plug_vote_options');
    }
};
