<?php

declare(strict_types=1);

namespace App\Comment;

use App\Form\Model\CommentModel;

readonly final class HoneypotDecider implements SpamDecider
{
    public function isSpam(CommentModel $commentModel): bool
    {
        return $commentModel->honeypot;
    }
}
