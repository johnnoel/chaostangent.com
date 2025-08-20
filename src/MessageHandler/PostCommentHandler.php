<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Comment\SpamDecider;
use App\Entity\Comment;
use App\Message\PostComment;
use App\Notifier\CommentPostedNotification;
use App\Repository\CommentRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;

#[AsMessageHandler]
readonly final class PostCommentHandler
{
    public function __construct(
        private CommentRepository $commentRepository,
        private SpamDecider $spamDecider,
        private NotifierInterface $notifier
    ) {
    }

    public function __invoke(PostComment $message): Comment
    {
        $model = $message->commentModel;
        $approved = true;
        $spam = false;

        if ($this->spamDecider->isSpam($model)) {
            $approved = false;
            $spam = true;
        }

        $comment = Comment::createFromCommentModel($message->commentModel, $message->post, $approved, $spam);
        $this->commentRepository->create($comment);

        $this->notifier->send(new CommentPostedNotification($comment), new Recipient('jn@ceetea.uk'));

        return $comment;
    }
}
