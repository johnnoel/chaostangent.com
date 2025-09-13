<?php

declare(strict_types=1);

namespace App\Image;

enum LoadingType: string
{
    case AUTO = 'auto';
    case EAGER = 'eager';
    case LAZY = 'lazy';
}
