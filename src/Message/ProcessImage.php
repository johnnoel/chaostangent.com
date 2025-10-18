<?php

declare(strict_types=1);

namespace App\Message;

use App\Image\Source;

readonly final class ProcessImage
{
    public function __construct(public Source $source, public bool $force)
    {
    }
}
