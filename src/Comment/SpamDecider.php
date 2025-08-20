<?php

declare(strict_types=1);

namespace App\Comment;

use App\Form\Model\CommentModel;

interface SpamDecider
{
    public function isSpam(CommentModel $commentModel): bool;
}
