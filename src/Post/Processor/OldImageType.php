<?php

declare(strict_types=1);

namespace App\Post\Processor;

enum OldImageType: string
{
    case OLDTHUMB = 'oldthumb';
    case OLDLEAD = 'oldlead';
    case OLDPOSTER = 'oldposter';
    case OLDSQUARE = 'oldsquare';

    public function getSize(): string
    {
        return match ($this) {
            self::OLDTHUMB => '268x117',
            self::OLDLEAD => '540x231',
            self::OLDPOSTER => '544x306',
            self::OLDSQUARE => '320x320',
        };
    }
}
