<?php

declare(strict_types=1);

namespace App\Message;

readonly final class ImportPost
{
    public function __construct(public string $content)
    {
    }
}
