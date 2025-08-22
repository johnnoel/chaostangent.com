<?php

declare(strict_types=1);

namespace App\Image;

final class GatheringImageRepository implements ImageRepository
{
    /** @var array<Source> */
    public private(set) array $sources = [];

    public function __construct(private readonly ImageRepository $decoratedImageRepository)
    {
    }

    /** @inheritdoc */
    #[\Override]
    public function getVariants(Source $source): array
    {
        $this->sources[] = $source;

        return $this->decoratedImageRepository->getVariants($source);
    }

    public function reset(): void
    {
        $this->sources = [];
    }
}
