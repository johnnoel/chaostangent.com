<?php

declare(strict_types=1);

namespace App\Image;

use JsonSerializable;

readonly final class Variant implements JsonSerializable
{
    public function __construct(public string $src, public MimeType $mimeType, public int $width, public int $height)
    {
    }

    public function jsonSerialize(): mixed
    {
        return array_filter([ // to get rid of 0'd width or height properties
            'src' => $this->src,
            'type' => $this->mimeType->value,
            'width' => $this->width,
            'height' => $this->height,
        ]);
    }
}
