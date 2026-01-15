<?php

namespace App\Modules\Notification\Events;

use App\Modules\Comment\Models\Comment;
use App\Modules\Post\Models\Post;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostCommented
{
    use Dispatchable, SerializesModels;

    public User $sender;
    public User $receiver;
    public Post $post;
    public Comment $comment;

    public function __construct(User $sender, User $receiver, Post $post, Comment $comment)
    {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->post = $post;
        $this->comment = $comment;
    }
}