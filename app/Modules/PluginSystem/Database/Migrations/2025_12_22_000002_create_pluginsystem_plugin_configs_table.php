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
        Schema::create('pluginsystem_plugin_configs', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_name'); // 插件名称
            $table->string('config_key'); // 配置项键名
            $table->string('config_label'); // 配置项显示标签
            $table->string('config_type')->default('text'); // 配置类型: text, password, select, checkbox, textarea, number, email, url
            $table->text('config_value')->nullable(); // 配置值
            $table->text('config_default')->nullable(); // 默认值
            $table->json('config_options')->nullable(); // 选择项配置 (用于select类型)
            $table->text('config_description')->nullable(); // 配置项描述
            $table->string('config_group')->default('general'); // 配置分组
            $table->integer('config_order')->default(0); // 显示顺序
            $table->boolean('is_required')->default(false); // 是否必填
            $table->string('validation_rules')->nullable(); // 验证规则

            // 按钮相关字段
            $table->string('button_action')->nullable()->comment('按钮操作标识');
            $table->string('button_variant')->default('primary')->comment('按钮样式：primary, secondary, success, danger, warning, info, light, dark');
            $table->text('button_confirm')->nullable()->comment('按钮确认提示信息');
            $table->boolean('button_disabled')->default(false)->comment('按钮是否禁用');

            // 数据列表相关字段
            $table->json('data_source')->nullable()->comment('数据源配置');
            $table->json('table_columns')->nullable()->comment('表格列配置');
            $table->json('table_actions')->nullable()->comment('表格操作配置');
            $table->json('table_filters')->nullable()->comment('表格筛选配置');
            $table->json('table_pagination')->nullable()->comment('表格分页配置');
            $table->json('table_operations')->nullable()->comment('表格操作配置');
            $table->json('table_search')->nullable()->comment('表格搜索配置');
            $table->json('table_default_sort')->nullable()->comment('表格默认排序配置');

            // 只读字段标识
            $table->boolean('is_readonly')->default(false)->comment('是否只读');

            $table->timestamps();

            // 索引
            $table->unique(['plugin_name', 'config_key']); // 每个插件的配置项键名唯一
            $table->index(['plugin_name', 'config_group']); // 按插件和分组查询
            $table->index('config_order'); // 排序用
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pluginsystem_plugin_configs');
    }
};
