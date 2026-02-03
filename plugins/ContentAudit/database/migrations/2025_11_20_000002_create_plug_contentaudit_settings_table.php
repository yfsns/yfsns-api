<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('plug_contentaudit_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('配置键');
            $table->text('value')->nullable()->comment('配置值');
            $table->string('type')->default('string')->comment('配置类型：string, int, bool, json');
            $table->string('group')->default('general')->comment('配置分组');
            $table->text('description')->nullable()->comment('配置描述');
            $table->boolean('is_public')->default(false)->comment('是否为公开配置');
            $table->timestamps();

            $table->index(['group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plug_contentaudit_settings');
    }
};
