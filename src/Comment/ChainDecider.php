<?php

declare(strict_types=1);

namespace App\Comment;

use App\Form\Model\CommentModel;

readonly final class ChainDecider implements SpamDecider
{
    /**
     * @param iterable<SpamDecider> $deciders
     */
    public function __construct(private iterable $deciders)
    {
    }

    public function isSpam(CommentModel $commentModel): bool
    {
        foreach ($this->deciders as $decider) {
            if ($decider->isSpam($commentModel)) {
                return true;
            }
        }

        return false;
    }
}
