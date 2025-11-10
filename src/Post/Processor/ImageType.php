<?php

declare(strict_types=1);

namespace App\Post\Processor;

enum ImageType: string
{
    case THUMB = 'thumb';
    case LEAD = 'lead';
    case POSTER = 'poster';
    case SQUARE = 'square';

    public static function fromOldType(OldImageType $oldImageType): self
    {
        return match ($oldImageType) {
            OldImageType::OLDTHUMB => self::THUMB,
            OldImageType::OLDLEAD => self::LEAD,
            OldImageType::OLDPOSTER => self::POSTER,
            OldImageType::OLDSQUARE => self::SQUARE,
        };
    }
}
