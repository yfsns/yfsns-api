<?php

namespace App\Modules\Notification\Events;

use App\Modules\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn
{
    use Dispatchable, SerializesModels;

    public User $user;
    public string $ip;
    public string $device;
    public string $location;

    public function __construct(User $user, string $ip, string $device, string $location = '')
    {
        $this->user = $user;
        $this->ip = $ip;
        $this->device = $device;
        $this->location = $location;
    }
}
