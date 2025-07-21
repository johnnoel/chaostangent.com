<?php

declare(strict_types=1);

namespace App\Message;

use App\Image\MimeType;
use App\Image\Source;

readonly final class TransformImage
{
    public function __construct(public Source $source, public MimeType $mimeType)
    {
    }
}
