<?php

declare(strict_types=1);

namespace App\Image;

enum MimeType: string
{
    case JPEG = 'image/jpeg';
    case AVIF = 'image/avif';
    case WEBP = 'image/webp';
    case JPEG_XL = 'image/jxl';
    case PNG = 'image/png';

    public function getExtension(): string
    {
        return match ($this) {
            self::JPEG => 'jpg',
            self::AVIF => 'avif',
            self::WEBP => 'webp',
            self::JPEG_XL => 'jxl',
            self::PNG => 'png',
        };
    }
}
