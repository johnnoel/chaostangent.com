<?php

declare(strict_types=1);

namespace App\Notifier;

use App\Entity\Comment;
use Symfony\Component\Notifier\Notification\Notification;

class CommentPostedNotification extends Notification
{
    public function __construct(private readonly Comment $comment)
    {
        $post = $comment->getPost();
        $subject = sprintf(
            'New comment posted to "%s" by "%s" <%s>',
            $post->getFullTitle(),
            $this->comment->getAuthorName(),
            $this->comment->getAuthorEmail()
        );

        parent::__construct($subject, [ 'email' ]);

        $this->content($this->comment->getComment());
    }
}
