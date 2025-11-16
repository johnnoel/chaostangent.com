<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * @implements DataTransformerInterface<array<Tag>,array<string>>
 */
readonly final class TagsTransformer implements DataTransformerInterface
{
    public function __construct(
        private TagRepository $tagRepository,
        private bool $createIfNotFound = true
    ) {
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

        $foundTags = $tags->map(fn (Tag $tag) => $tag->getTag())->all();
        $notFound = array_diff($value, $foundTags);

        if (count($notFound) > 0) {
            if (!$this->createIfNotFound) {
                throw new TransformationFailedException(
                    'Could not find existing tags for: ' . var_export($notFound, true)
                );
            }

            $slugger = new AsciiSlugger();
            $toCreate = array_map(
                fn (string $t): Tag => new Tag($t, $slugger->slug($t)->lower()->toString()),
                $notFound
            );

            $this->tagRepository->createMany(...$toCreate);
            $tags = $tags->merge($toCreate);
        }

        return $tags->all();
    }
}
