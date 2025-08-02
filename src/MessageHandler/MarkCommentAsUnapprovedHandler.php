<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\MarkCommentAsUnapproved;
use App\Repository\CommentRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class MarkCommentAsUnapprovedHandler
{
    public function __construct(private CommentRepository $commentRepository)
    {
    }

    public function __invoke(MarkCommentAsUnapproved $message): void
    {
        $comment = $message->comment;
        $comment->markAsUnapproved();

        $this->commentRepository->update($comment);
    }
}
