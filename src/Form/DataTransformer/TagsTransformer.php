<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<array<Tag>,array<string>>
 */
readonly final class TagsTransformer implements DataTransformerInterface
{
    public function __construct(private TagRepository $tagRepository)
    {
    }

    /**
     * @return array<string>|null
     */
    public function transform(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $ret = [];

        foreach ($value as $tag) {
            if (!($tag instanceof Tag)) { /** @phpstan-ignore instanceof.alwaysTrue */
                throw new TransformationFailedException('$value is not a collection of tags');
            }

            $ret[] = $tag->getTag();
        }

        return $ret;
    }

    /**
     * @return array<Tag>
     */
    public function reverseTransform(mixed $value): array
    {
        if (!is_array($value) || count($value) === 0) {
            return [];
        }

        $rawTags = array_unique(array_filter(array_map('trim', $value)));
        $tags = $this->tagRepository->findOrCreate($rawTags);

        if ($tags->isEmpty()) {
            throw new TransformationFailedException('No tags found for ' . var_export($value, true));
        }

        return $tags->all();
    }
}
