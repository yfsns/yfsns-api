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
        // 删除可能存在的旧表，避免冲突
        Schema::dropIfExists('pluginsystem_plugin_installations');
        Schema::dropIfExists('pluginsystem_enabled_plugins'); // 清理旧的启用插件表

        Schema::create('pluginsystem_plugin_installations', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_name')->unique();
            $table->string('version')->default('1.0.0');

            // 安装状态：插件是否已安装（数据库迁移、基础数据等）
            $table->boolean('installed')->default(false);
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('uninstalled_at')->nullable();

            // 启用状态：插件是否已启用（路由注册等）
            $table->boolean('enabled')->default(false);
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('disabled_at')->nullable();

            $table->timestamps();

            $table->index(['plugin_name', 'installed']);
            $table->index(['plugin_name', 'enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pluginsystem_plugin_installations');
        Schema::dropIfExists('pluginsystem_enabled_plugins');
    }
};
