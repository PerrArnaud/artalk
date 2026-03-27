<?php

namespace App\Event;

use App\Entity\Comment;
use Symfony\Contracts\EventDispatcher\Event;

class CommentCreatedEvent extends Event
{
    public const NAME = 'comment.created';

    public function __construct(
        private Comment $comment
    ) {
    }

    public function getComment(): Comment
    {
        return $this->comment;
    }
}
