<?php

declare(strict_types=1);

namespace App\Extension\Serializer\JsonLd;

use App\Entity\Post;
use App\Entity\Tag;
use App\Post\FeedUrlGenerator;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly final class PostNormaliser implements NormalizerInterface
{
    public function __construct(
        private string $title,
        private FeedUrlGenerator $urlGenerator,
    ) {
    }

    /** @inheritdoc */
    #[\Override]
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        if (!($data instanceof Post)) {
            throw new InvalidArgumentException('$data must be an instance of ' . Post::class);
        }

        $post = $data;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            '@id' => $this->urlGenerator->getPostUrl($post),
            'headline' => $post->getFullTitle(),
            'name' => $post->getFullTitle(),
            'description' => $post->getSummary() ?? $post->getContent(),
            'datePublished' => $post->getDate()?->format('Y-m-d'),
            'dateModified' => $post->getUpdated()->format('Y-m-d'),
            'url' => $this->urlGenerator->getPostUrl($post),
            'isPartOf' => [
                '@type' => 'Blog',
                '@id' => $this->urlGenerator->getHomeUrl(),
                'name' => $this->title,
            ],
            'keywords' => array_map(fn (Tag $t): string => $t->getTag(), $post->getTags()->toArray()),
            'author' => [
                '@type' => 'Person',
                'name' => $post->getAuthor(),
            ],
            'commentCount' => $post->getComments()->count(),
            'comment' => $post->getComments()->toArray(),
        ];
    }

    /** @inheritdoc */
    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Post && $format === Encoder::FORMAT;
    }

    /** @inheritdoc */
    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [
            Post::class => true,
        ];
    }
}
