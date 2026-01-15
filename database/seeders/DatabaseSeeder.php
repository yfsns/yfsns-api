<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 权限和角色相关（按顺序执行）
            \App\Modules\User\Database\Seeders\UserRoleSeeder::class,
            \App\Modules\User\Database\Seeders\UserRolePermissionSeeder::class,

            // 用户数据
            \App\Modules\User\Database\Seeders\UserSeeder::class,

            // 系统配置
            \App\Modules\System\Database\Seeders\SystemConfigSeeder::class,
            \App\Modules\SensitiveWord\Database\Seeders\SensitiveWordSeeder::class,

            // 通知模块数据（按顺序执行）
            \App\Modules\Notification\Database\Seeders\EmailServerSeeder::class,
            \App\Modules\Notification\Database\Seeders\NotificationTemplateSeeder::class,
        ]);
    }
}
