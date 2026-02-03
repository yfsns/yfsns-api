<?php

namespace Plugins\WechatLogin\Models;

use Illuminate\Database\Eloquent\Model;

class WechatConfig extends Model
{
    protected $table = 'plug_wechatlogin_configs';

    protected $fillable = [
        'app_id',
        'app_secret',
        'token',
        'aes_key',
        'mch_id',
        'mch_key',
        'cert_path',
        'key_path',
        'notify_url',
        'type',
        'is_active',
        'extra_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
