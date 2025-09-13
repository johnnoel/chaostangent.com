<?php

declare(strict_types=1);

namespace App\Image;

readonly final class CoreImageRepository implements ImageRepository
{
    public function __construct(private FileHandler $fileHandler)
    {
    }

    /** @inheritdoc */
    #[\Override]
    public function getVariants(
        Source $source,
        array $mimeTypes = [ MimeType::AVIF, MimeType::WEBP, MimeType::JPEG ]
    ): array {
        [ 'w' => $w, 'h' => $h ] = $source->getTargetSize();

        return array_map(
            fn (MimeType $mt): Variant => new Variant(
                $this->fileHandler->getVariantUrl($source, $mt),
                $mt,
                $w,
                $h
            ),
            $mimeTypes
        );
    }
}
