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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('位置标题/名称');
            $table->decimal('latitude', 10, 8)->comment('纬度');
            $table->decimal('longitude', 11, 8)->comment('经度');
            $table->string('address')->comment('完整地址');
            $table->string('country')->nullable()->comment('国家');
            $table->string('province')->nullable()->comment('省份');
            $table->string('city')->nullable()->comment('城市');
            $table->string('district')->nullable()->comment('区县');
            $table->string('place_name')->nullable()->comment('具体地点名称');
            $table->string('category')->nullable()->comment('地点类型：餐厅、景点、学校等');
            $table->integer('post_count')->default(0)->comment('使用该位置的帖子数量');
            $table->json('metadata')->nullable()->comment('扩展信息');
            $table->timestamps();

            // 索引
            $table->index('title', 'idx_title');
            $table->index(['latitude', 'longitude'], 'idx_lat_lng');
            $table->index('country', 'idx_country');
            $table->index('province', 'idx_province');
            $table->index('city', 'idx_city');
            $table->index('category', 'idx_category');
            $table->index('post_count', 'idx_post_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
