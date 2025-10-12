<?php

declare(strict_types=1);

namespace App\Image;

enum BlockType: string
{
    case THUMBNAILS = 'thumbnails';
    case SLIDESHOW = 'slideshow';

    public function getRegex(): string
    {
        return match ($this) {
            self::THUMBNAILS => '#<p class="thumbnails.*?">(.*?)</p>#s',
            self::SLIDESHOW => '#<p class="slideshow">(.*?)</p>#s',
        };
    }
}
