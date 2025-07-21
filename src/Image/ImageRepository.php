<?php

declare(strict_types=1);

namespace App\Image;

interface ImageRepository
{
    /**
     * @return array<string|int,Variant>
     */
    public function getVariants(Source $source): array;
}
