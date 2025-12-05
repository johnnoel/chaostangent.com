<?php

declare(strict_types=1);

namespace App\Message;

readonly final class ImportTweet
{
    /**
     * @param array<mixed> $tweet
     */
    public function __construct(public array $tweet)
    {
    }
}
