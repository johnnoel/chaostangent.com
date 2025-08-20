<?php

declare(strict_types=1);

namespace App\Comment;

enum AkismetResponse: string
{
    case SPAM = 'spam';
    case HAM = 'ham';
    case DISCARD = 'discard';
    case UNKNOWN = 'unknown';
}
