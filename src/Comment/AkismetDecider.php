<?php

declare(strict_types=1);

namespace App\Comment;

use App\Form\Model\CommentModel;
use RuntimeException;

readonly final class AkismetDecider implements SpamDecider
{
    public function __construct(private AkismetClient $akismet)
    {
    }

    public function isSpam(CommentModel $commentModel): bool
    {
        try {
            return $this->akismet->checkComment(
                strval($commentModel->authorIp),
                $commentModel->userAgent,
                $commentModel->referrer,
                strval($commentModel->postUrl),
                $commentModel->authorName,
                $commentModel->authorEmail,
                $commentModel->authorUrl,
                $commentModel->comment
            ) !== AkismetResponse::HAM;
        } catch (RuntimeException) {
        }

        return false;
    }
}
