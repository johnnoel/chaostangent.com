<?php

declare(strict_types=1);

namespace App\Image;

interface ImageRepository
{
    /**
     * @param array<MimeType> $mimeTypes
     * @return array<string|int,Variant>
     */
    public function getVariants(Source $source, array $mimeTypes = []): array;
}
