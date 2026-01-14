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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class UserPermission extends Model
{
    protected $table = 'user_permissions';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'module',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            UserRole::class,
            'user_role_permissions',
            'permission_id',
            'role_id'
        )->withTimestamps();
    }

    /**
     * 获取或创建指定权限标识，返回对应ID列表.
     */
    public static function ensureBySlugs(array $slugs): array
    {
        $ids = [];
        foreach ($slugs as $slug) {
            $slug = trim($slug);
            if ($slug === '' || str_contains($slug, '*')) {
                continue;
            }

            if (! str_contains($slug, '.')) {
                continue;
            }

            $module = Str::before($slug, '.');

            $permission = static::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => static::generateNameFromSlug($slug),
                    'module' => $module,
                ]
            );
            $ids[] = $permission->id;
        }

        return array_values(array_unique($ids));
    }

    protected static function generateNameFromSlug(string $slug): string
    {
        return str_replace(['.', '_'], ' ', ucfirst($slug));
    }
}
