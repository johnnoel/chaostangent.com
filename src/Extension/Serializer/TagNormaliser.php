<?php

declare(strict_types=1);

namespace App\Extension\Serializer;

use App\Entity\Tag;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly final class TagNormaliser implements NormalizerInterface
{
    /** @inheritdoc */
    #[\Override]
    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        if (!($data instanceof Tag)) {
            throw new InvalidArgumentException('$data must be an instance of ' . Tag::class);
        }

        return $data->getTag();
    }

    /** @inheritdoc */
    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Tag;
    }

    /** @inheritdoc */
    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [
            Tag::class => true,
        ];
    }
}
