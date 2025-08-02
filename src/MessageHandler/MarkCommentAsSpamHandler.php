<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\MarkCommentAsSpam;
use App\Repository\CommentRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class MarkCommentAsSpamHandler
{
    public function __construct(private CommentRepository $commentRepository)
    {
    }

    public function __invoke(MarkCommentAsSpam $message): void
    {
        $comment = $message->comment;
        $comment->markAsSpam();

        $this->commentRepository->update($comment);
    }
}
