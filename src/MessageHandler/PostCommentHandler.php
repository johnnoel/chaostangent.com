<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Comment;
use App\Message\PostComment;
use App\Notifier\CommentPostedNotification;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;

#[AsMessageHandler]
readonly final class PostCommentHandler
{
    public function __construct(private NotifierInterface $notifier)
    {
    }

    public function __invoke(PostComment $message): void
    {
        // is the honeypot filled in?
        // is the timer below a certain threshold?

        $comment = Comment::createFromCommentModel($message->commentModel, $message->post);
        $this->notifier->send(new CommentPostedNotification($comment), new Recipient('jn@ceetea.uk'));
    }
}
