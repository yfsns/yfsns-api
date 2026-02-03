<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('plug_wechatlogin_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->comment('配置类型：mp=公众号，mini=小程序，open=开放平台');
            $table->string('app_id')->comment('应用ID');
            $table->string('app_secret')->comment('应用密钥');
            $table->string('token')->nullable()->comment('令牌');
            $table->string('aes_key')->nullable()->comment('消息加解密密钥');
            $table->string('mch_id')->nullable()->comment('商户号');
            $table->string('mch_key')->nullable()->comment('商户密钥');
            $table->string('cert_path')->nullable()->comment('证书路径');
            $table->string('key_path')->nullable()->comment('私钥路径');
            $table->string('notify_url')->nullable()->comment('支付回调地址');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->text('extra_config')->nullable()->comment('额外配置JSON');
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plug_wechatlogin_configs');
    }
};
