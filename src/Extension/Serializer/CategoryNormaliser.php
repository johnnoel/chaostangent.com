<?php

declare(strict_types=1);

namespace App\Extension\Serializer;

use App\Entity\Category;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly final class CategoryNormaliser implements NormalizerInterface
{
    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        if (!($data instanceof Category)) {
            throw new InvalidArgumentException('$data must be an instance of ' . Category::class);
        }

        return $data->getTitle();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Category;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Category::class => true,
        ];
    }
}
