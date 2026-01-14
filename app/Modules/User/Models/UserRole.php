<?php

/**
 * YFSNS社交网络服务系统
 *
 * Copyright (C) 2025 合肥音符信息科技有限公司
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Modules\User\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use function in_array;
use function is_string;

/**
 * 用户角色模型.
 *
 * 提供用户角色的基础功能，包括权限管理、删除保护等
 */
class UserRole extends Model
{
    use SoftDeletes;

    /**
     * 用户角色类型：普通用户角色.
     */
    public const TYPE_NORMAL = 1;

    /**
     * 用户角色类型：付费用户角色.
     */
    public const TYPE_PREMIUM = 2;

    /**
     * 系统预设角色：管理员.
     */
    public const SYSTEM_GROUP_ADMIN = 'admin';

    /**
     * 系统预设角色：访客.
     */
    public const SYSTEM_GROUP_GUEST = 'guest';

    /**
     * 系统预设角色：注册用户.
     */
    public const SYSTEM_GROUP_REGISTERED = 'user';

    /**
     * 系统预设角色：VIP 用户（付费）.
     */
    public const SYSTEM_GROUP_VIP = 'vip';

    /**
     * 状态：禁用.
     */
    public const STATUS_DISABLED = 0;

    /**
     * 状态：启用.
     */
    public const STATUS_ENABLED = 1;

    /**
     * 指定表名.
     */
    protected $table = 'user_roles';

    /**
     * 可批量赋值的属性.
     */
    protected $fillable = [
        'key',         // 添加key字段
        'name',        // 用户角色名称
        'description', // 用户角色描述
        'permissions', // 权限列表（JSON格式）
        'type',        // 用户角色类型
        'status',      // 状态
        'sort',        // 排序
        'is_system',   // 是否为系统用户角色
    ];

    /**
     * 属性类型转换.
     */
    protected $casts = [
        'permissions' => 'array',
        'type' => 'integer',
        'status' => 'integer',
        'sort' => 'integer',
        'is_system' => 'boolean',
    ];

    /**
     * 获取状态文本.
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '启用',
            default => '未知',
        };
    }

    /**
     * 获取类型文本.
     */
    public function getTypeTextAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_NORMAL => '普通用户角色',
            self::TYPE_PREMIUM => '付费用户角色',
            default => '未知',
        };
    }

    /**
     * 关联用户.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    /**
     * 检查是否有某个权限.
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->getPermissions();

        if (in_array('*', $permissions, true)) {
            return true;
        }

        if (in_array($permission, $permissions, true)) {
            return true;
        }

        // 支持 post.* 这类前缀匹配
        $segments = explode('.', $permission);
        $prefix = '';
        foreach ($segments as $segment) {
            $prefix = $prefix === '' ? $segment : $prefix . '.' . $segment;
            if (in_array($prefix . '.*', $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取权限列表.
     */
    public function getPermissions(): array
    {
        // 从 user_role_permissions 表读取权限
        return DB::table('user_role_permissions')
            ->where('role_id', $this->id)
            ->pluck('permission_slug')
            ->toArray();
    }

    /**
     * 同步角色权限.
     */
    public function syncPermissions(array $permissions): void
    {
        // 清理权限：只保留有效的字符串权限标识
        $permissions = array_values(array_unique(array_filter($permissions, fn ($item) => is_string($item) && $item !== '')));

        // 删除旧权限
        DB::table('user_role_permissions')->where('role_id', $this->id)->delete();

        // 插入新权限
        if (! empty($permissions)) {
            $data = array_map(function ($slug) {
                return [
                    'role_id' => $this->id,
                    'permission_slug' => $slug,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $permissions);

            DB::table('user_role_permissions')->insert($data);
        }

        // 同时更新 JSON 字段（保持兼容性）
        $this->forceFill(['permissions' => $permissions])->save();
    }

    /**
     * 检查是否为系统用户角色.
     */
    public function isSystemGroup(): bool
    {
        return $this->is_system === true;
    }

    /**
     * 检查是否为付费用户角色.
     */
    public function isPremiumGroup(): bool
    {
        return $this->type === self::TYPE_PREMIUM;
    }

    /**
     * 检查是否为系统预设角色.
     */
    public function isSystemPresetRole(): bool
    {
        $presetKeys = [
            self::SYSTEM_GROUP_ADMIN,
            self::SYSTEM_GROUP_REGISTERED,
            self::SYSTEM_GROUP_GUEST,
            self::SYSTEM_GROUP_VIP,
        ];

        return in_array($this->key, $presetKeys);
    }

    /**
     * 检查是否允许删除.
     */
    public function canBeDeleted(): bool
    {
        // 系统角色不允许删除
        if ($this->is_system || $this->isSystemPresetRole()) {
            return false;
        }

        // 检查是否有用户使用该角色
        return ! ($this->users()->exists());
    }

    /**
     * 获取删除限制原因.
     */
    public function getDeleteRestrictionReason(): ?string
    {
        if ($this->is_system || $this->isSystemPresetRole()) {
            return '系统预设角色不允许删除';
        }

        if ($this->users()->exists()) {
            return '该角色下还有用户，无法删除';
        }

        return null;
    }
}
