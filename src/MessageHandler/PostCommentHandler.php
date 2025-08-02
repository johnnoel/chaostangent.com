<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\PostComment;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class PostCommentHandler
{
    public function __invoke(PostComment $message): void
    {
        // is the honeypot filled in?
        // is the timer below a certain threshold?
    }
}
