<?php

declare(strict_types=1);

namespace App\Comment;

use App\Form\Model\CommentModel;
use DateInterval;
use DateTimeImmutable;
use Symfony\Component\Clock\ClockAwareTrait;

readonly final class TimeTakenDecider implements SpamDecider
{
    use ClockAwareTrait;

    private const string TIME_TAKEN = 'PT10S';

    public function isSpam(CommentModel $commentModel): bool
    {
        $formRendered = $commentModel->formRendered;

        if (!($formRendered instanceof DateTimeImmutable)) {
            return false;
        }

        return $this->now() < $formRendered->add(new DateInterval(self::TIME_TAKEN));
    }
}
