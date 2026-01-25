<?php

declare(strict_types=1);

namespace App\Image;

final class GatheringImageRepository implements ImageRepository
{
    /** @var array<Source> */
    public private(set) array $sources = [];
    /** @var array<Variant> */
    public private(set) array $variants = [];

    public function __construct(private readonly ImageRepository $decoratedImageRepository)
    {
    }

    /** @inheritdoc */
    #[\Override]
    public function getVariants(
        Source $source,
        array $mimeTypes = [ MimeType::AVIF, MimeType::WEBP, MimeType::JPEG ]
    ): array {
        $this->sources[] = $source;

        $variants = $this->decoratedImageRepository->getVariants($source, $mimeTypes);
        $this->variants = array_merge($this->variants, $variants);

        return $variants;
    }

    public function reset(): void
    {
        $this->sources = [];
    }
}
