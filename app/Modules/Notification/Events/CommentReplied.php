<?php

namespace App\Modules\Notification\Events;

use App\Modules\Comment\Models\Comment;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentReplied
{
    use Dispatchable, SerializesModels;

    public User $sender;
    public User $receiver;
    public Comment $originalComment;
    public Comment $replyComment;

    public function __construct(User $sender, User $receiver, Comment $originalComment, Comment $replyComment)
    {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->originalComment = $originalComment;
        $this->replyComment = $replyComment;
    }
}
