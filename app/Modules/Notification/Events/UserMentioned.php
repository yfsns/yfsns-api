<?php

namespace App\Modules\Notification\Events;

use App\Modules\User\Models\User;
use App\Modules\Post\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserMentioned
{
    use Dispatchable, SerializesModels;

    public User $sender;
    public User $receiver;
    public Post $post;

    public function __construct(User $sender, User $receiver, Post $post)
    {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->post = $post;
    }
}
