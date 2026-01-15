<?php

namespace App\Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => '管理员',
                'key' => 'admin',
                'description' => '系统管理员',
                'permissions' => ['*'],
                'status' => true,
                'type' => 1,
                'sort' => 0,
                'is_system' => true,
                'is_default' => false,
            ],
            [
                'name' => '注册用户',
                'key' => 'user',
                'description' => '注册用户',
                'permissions' => [
                    'post.view',
                    'post.text',
                    'post.delete',
                    'comment.create',
                ],
                'status' => true,
                'type' => 0,
                'sort' => 1,
                'is_system' => true,
                'is_default' => true,
            ],
            [
                'name' => 'VIP 用户',
                'key' => 'vip',
                'description' => '付费会员，拥有更多创作能力',
                'permissions' => [
                    'post.view',
                    'post.text',
                    'post.image',
                    'post.video',
                    'post.delete',
                    'comment.create',
                    'topic.create',
                ],
                'status' => true,
                'type' => 1,
                'sort' => 2,
                'is_system' => true,
                'is_default' => false,
            ],
            [
                'name' => '访客',
                'key' => 'guest',
                'description' => '未登录用户组',
                'permissions' => [
                    'post.view',
                ],
                'status' => true,
                'type' => 0,
                'sort' => 3,
                'is_system' => true,
                'is_default' => false,
            ],
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'] ?? [];
            $payload = Arr::except($roleData, ['permissions']);

            /** @var \App\Modules\User\Models\UserRole $role */
            $role = \App\Modules\User\Models\UserRole::updateOrCreate(
                ['key' => $roleData['key']],
                $payload
            );

            if (! empty($permissions)) {
                $role->syncPermissions($permissions);
            }
        }

        $this->command->info(' 用户角色已同步。');
    }
}
