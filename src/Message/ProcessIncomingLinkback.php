<?php

declare(strict_types=1);

namespace App\Message;

readonly final class ProcessIncomingLinkback
{
    public function __construct(public string $source, public string $target, public string $ipAddress)
    {
    }
}
