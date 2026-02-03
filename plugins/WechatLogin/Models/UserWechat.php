<?php

namespace Plugins\WechatLogin\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWechat extends Model
{
    protected $table = 'plug_wechatlogin_user_wechats';

    protected $fillable = [
        'user_id',
        'openid',
        'unionid',
        'nickname',
        'avatar',
        'province',
        'city',
        'country',
        'sex',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
