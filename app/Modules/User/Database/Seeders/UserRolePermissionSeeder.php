<?php

namespace App\Modules\User\Database\Seeders;

use App\Modules\User\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use function is_string;

class UserRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 清空现有权限数据
        DB::table('user_role_permissions')->truncate();

        // 获取所有角色
        $roles = UserRole::all()->keyBy('key');

        // 管理员：拥有所有权限（使用 * 表示）
        if ($roles->has('admin')) {
            $this->syncRolePermissions($roles['admin']->id, ['*']);
        }

        // 注册用户：基础权限
        if ($roles->has('user')) {
            $this->syncRolePermissions($roles['user']->id, [
                'post.view',
                'post.text',
                'post.delete',
                'comment.create',
            ]);
        }

        // VIP 用户：更多权限
        if ($roles->has('vip')) {
            $this->syncRolePermissions($roles['vip']->id, [
                'post.view',
                'post.text',
                'post.image',
                'post.video',
                'post.delete',
                'comment.create',
                'topic.create',
            ]);
        }

        // 访客：只读权限
        if ($roles->has('guest')) {
            $this->syncRolePermissions($roles['guest']->id, [
                'post.view',
            ]);
        }

        $this->command->info(' 用户角色权限已填充。');
    }

    /**
     * 同步角色权限到 user_role_permissions 表.
     */
    private function syncRolePermissions(int $roleId, array $permissions): void
    {
        // 清理权限：只保留有效的字符串权限标识
        $permissions = array_values(array_unique(array_filter($permissions, fn ($item) => is_string($item) && $item !== '')));

        // 删除旧权限
        DB::table('user_role_permissions')->where('role_id', $roleId)->delete();

        // 插入新权限
        if (! empty($permissions)) {
            $data = array_map(function ($slug) use ($roleId) {
                return [
                    'role_id' => $roleId,
                    'permission_slug' => $slug,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $permissions);

            DB::table('user_role_permissions')->insert($data);
        }
    }
}
