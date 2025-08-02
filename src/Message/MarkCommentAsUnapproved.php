<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\Comment;

readonly final class MarkCommentAsUnapproved
{
    public function __construct(public Comment $comment)
    {
    }
}
