<?php

declare(strict_types=1);

namespace App\Image;

readonly final class Variant
{
    public function __construct(public string $src, public MimeType $mimeType, public int $width, public int $height)
    {
    }
}
