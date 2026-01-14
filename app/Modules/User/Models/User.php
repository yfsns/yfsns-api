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

use App\Http\Traits\IpRecordTrait;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, IpRecordTrait, Notifiable, SoftDeletes;

    /**
     * 用户状态：启用.
     */
    public const STATUS_ENABLED = 1;

    /**
     * 用户状态：禁用.
     */
    public const STATUS_DISABLED = 0;

    /**
     * 用户角色：普通用户.
     */
    public const GROUP_USER = 1;

    /**
     * 用户角色：VIP用户.
     */
    public const GROUP_VIP = 2;

    /**
     * 性别：男.
     */
    public const GENDER_MALE = 1;

    /**
     * 性别：女.
     */
    public const GENDER_FEMALE = 2;

    /**
     * 性别：其他.
     */
    public const GENDER_OTHER = 0;

    /**
     * 可批量赋值的属性.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'role_id',
        'username',
        'phone',
        'nickname',
        'gender',
        'birthday',
        'bio',
        'register_ip',
        'register_ip_location',
        'last_login_ip',
        'last_login_ip_location',
        'avatar',
    ];

    /**
     * 应该被隐藏的属性.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 应该被转换的属性.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'birthday' => 'date',
        'email_verified_at' => 'datetime',
        'status' => 'integer',
        'is_admin' => 'boolean',
        // 头像审核相关字段
    ];


    /**
     * 基础用户信息字段（用于关联查询）
     */
    public const BASIC_FIELDS = 'id,username,nickname,avatar';

    /**
     * 完整用户信息字段
     */
    public const FULL_FIELDS = 'id,username,nickname,email,gender,birthday,bio,status,is_admin,phone,email_verified_at,created_at';


    /**
     * 获取用户基础信息（用于API响应）
     */
    public function getBasicInfo(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'nickname' => $this->nickname,
        ];
    }

    /**
     * 作用域：只选择基础字段
     */
    public function scopeSelectBasic($query)
    {
        return $query->selectRaw(self::BASIC_FIELDS);
    }


    /**
     * 作用域：加载基础字段 + 额外字段
     * 使用方法：User::withEssentialFields('status,is_admin')->get()
     */
    public function scopeWithEssentialFields($query, string $extraFields = '')
    {
        $fields = $extraFields ? self::BASIC_FIELDS . ',' . $extraFields : self::BASIC_FIELDS;
        return $query->selectRaw($fields);
    }

    /**
     * 作用域：加载完整字段
     */
    public function scopeWithFullFields($query)
    {
        return $query->selectRaw(self::FULL_FIELDS);
    }


    /**
     * 获取用户头像信息（包含审核状态）
     */
    public function getAvatarInfo(): array
    {
        $avatarService = app(\App\Modules\User\Services\AvatarReviewService::class);
        $reviewStatus = $avatarService->getUserAvatarReviewStatus($this);

        return [
            'avatar_url' => $this->avatar ? config('app.url') . '/storage/' . $this->avatar : config('app.url') . '/assets/default_avatars.png',
            'review_status' => $reviewStatus['review_status'],
            'pending_review' => $reviewStatus['pending_asset'] !== null,
            'can_upload' => $reviewStatus['can_upload'],
        ];
    }

    /**
     * 获取API资源格式的用户基础信息
     * 用于在各种Resource中统一返回用户信息
     */
    public function getBasicApiInfo(): array
    {
        $avatarInfo = $this->getAvatarInfo();

        return [
            'id' => $this->id,
            'username' => $this->username,
            'nickname' => $this->nickname,
            'avatar_url' => $avatarInfo['avatar_url'],
            'avatar_review_status' => $avatarInfo['review_status'],
        ];
    }

    /**
     * 获取用户关系加载字符串（用于Eloquent with()方法）
     * 这有助于减少硬编码，降低耦合度
     */
    public static function getRelationString(?string $fields = null): string
    {
        return $fields ? self::BASIC_FIELDS . ',' . $fields : self::BASIC_FIELDS;
    }

    /**
     * 获取用户角色（首选）.
     */
    public function role()
    {
        return $this->belongsTo(UserRole::class, 'role_id');
    }


    /**
     * 获取用户资源文件.
     */
    public function assets()
    {
        return $this->hasMany(UserAsset::class);
    }

    /**
     * 获取用户指定类型的资源文件.
     */
    public function getAsset(string $type)
    {
        return $this->assets()->where('type', $type)->first();
    }

    /**
     * 获取用户背景图.
     */
    public function background()
    {
        return $this->getAsset(UserAsset::TYPE_BACKGROUND);
    }

    /**
     * 获取用户相册.
     */
    public function album()
    {
        return $this->assets()->where('type', UserAsset::TYPE_ALBUM);
    }

    /**
     * 获取用户动态
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * 获取用户的粉丝（关注这个用户的人）
     */
    public function followers()
    {
        return $this->hasMany(UserFollow::class, 'following_id');
    }

    /**
     * 获取用户关注的人
     */
    public function following()
    {
        return $this->hasMany(UserFollow::class, 'follower_id');
    }

    /**
     * 检查用户是否启用.
     */
    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    // ==================== Query Scopes ====================

    /**
     * 作用域：只查询启用状态的用户.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 作用域：只查询禁用状态的用户.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDisabled($query)
    {
        return $query->where('status', self::STATUS_DISABLED);
    }

    /**
     * 作用域：按手机号查询.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                 $phone 手机号
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }

    /**
     * 作用域：按邮箱查询.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                               $email 邮箱
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * 作用域：按用户名查询.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $username 用户名
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUsername($query, string $username)
    {
        return $query->where('username', $username);
    }

    // ==================== Accessors ====================

    /**
     * 获取状态文本（Accessor）.
     *
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        return match ((int) $this->status) {
            self::STATUS_ENABLED => '正常',
            self::STATUS_DISABLED => '禁用',
            default => '未知',
        };
    }

    /**
     * 检查用户是否是管理员.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * 检查是否有某个权限.
     */
    public function hasPermission(string $permission): bool
    {
        // 如果是管理员，拥有所有权限
        if ($this->isAdmin()) {
            return true;
        }

        // 检查用户角色权限
        return $this->role?->hasPermission($permission) ?? false;
    }

    /**
     * 获取所有权限.
     */
    public function getPermissions(): array
    {
        return $this->role?->getPermissions() ?? [];
    }



    /**
     * 获取用户通知设置（访问器）
     */
    public function getNotificationSettingsAttribute(): array
    {
        // 从用户 settings JSON 字段获取通知设置，如果不存在则返回默认设置
        $settings = $this->settings ? json_decode($this->settings, true) : [];

        return [
            'mention' => $settings['notification_mention'] ?? true,
            'like' => $settings['notification_like'] ?? true,
            'comment' => $settings['notification_comment'] ?? true,
            'comment_reply' => $settings['notification_comment_reply'] ?? true,
            'login_alert' => $settings['notification_login_alert'] ?? false,
        ];
    }

    /**
     * 获取用于认证的用户数据.
     */
    public function toAuthArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'name' => $this->name,
            'phone' => $this->phone,
            'nickname' => $this->nickname,
            'status' => $this->status,
            'is_admin' => $this->is_admin,
            'role_id' => $this->role_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }


    /**
     * 模型的事件映射.
     */
    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (! empty($user->role_id)) {
                return;
            }

            $defaultRoleId = UserRole::query()
                ->where('key', UserRole::SYSTEM_GROUP_REGISTERED)
                ->value('id');

            if ($defaultRoleId) {
                $user->role_id = $defaultRoleId;
            }
        });
    }
}
