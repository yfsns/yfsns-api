<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('plug_wechatlogin_user_wechats', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('关联用户ID');
            $table->string('openid')->unique()->comment('微信 openid');
            $table->string('unionid')->nullable()->index()->comment('微信 unionid');
            $table->string('nickname')->nullable()->comment('昵称');
            $table->string('avatar')->nullable()->comment('头像');
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->tinyInteger('sex')->default(0)->comment('性别 0未知 1男 2女');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plug_wechatlogin_user_wechats');
    }
};
