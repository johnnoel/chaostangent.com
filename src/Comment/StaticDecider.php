<?php

declare(strict_types=1);

namespace App\Comment;

use App\Form\Model\CommentModel;

readonly final class StaticDecider implements SpamDecider
{
    public function __construct(private bool $isSpam)
    {
    }

    public function isSpam(CommentModel $commentModel): bool
    {
        return $this->isSpam;
    }
}
