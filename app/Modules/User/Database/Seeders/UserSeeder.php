<?php

namespace App\Modules\User\Database\Seeders;

use App\Modules\User\Models\User;
use App\Modules\User\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 获取管理员用户角色
        $adminRole = UserRole::where('key', 'admin')->first();
        if (! $adminRole) {
            $this->command->error('管理员角色不存在，请先运行 UserRoleSeeder');

            return;
        }

        // 检查管理员是否已存在
        if (User::where('username', 'admin')->exists()) {
            $this->command->info('管理员用户已存在，跳过创建。');

            return;
        }

        // 创建管理员用户
        User::create([
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'email' => 'admin@example.com',
            'nickname' => '超级管理员',
            'is_admin' => true,
            'role_id' => $adminRole->id,
            'status' => 1,
        ]);

        $this->command->info(' 管理员用户创建成功！');
        $this->command->info('   用户名：admin');
        $this->command->info('   密码：password123');
    }
}
