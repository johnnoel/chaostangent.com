<?php

declare(strict_types=1);

namespace App\Extension\Serializer\JsonLd;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Tag;
use App\Post\FeedUrlGenerator;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Twig\Environment;

readonly final class PostNormaliser implements NormalizerInterface
{
    public function __construct(
        private string $title,
        private FeedUrlGenerator $urlGenerator,
        private Environment $twig,
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
            'description' => $this->twig->createTemplate($post->getSummary() ?? $post->getContent())->render(),
            'datePublished' => $post->getDate()?->format('Y-m-d\\TH:i:sP'),
            'dateModified' => $post->getUpdated()->format('Y-m-d\\TH:i:sP'),
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
            'comment' => array_map(fn (Comment $c): array => ([
                '@type' => 'Comment',
                '@id' => $this->urlGenerator->getCommentUrl($c),
                'dateCreated' => $c->getCreated()->format('Y-m-d\\TH:i:sP'),
                'description' => $c->getComment(),
                'author' => [
                    '@type' => 'Person',
                    'name' => $c->getAuthorName(),
                    'url' => $c->getAuthorUrl(),
                ],
            ]), $post->getComments()->toArray()),
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
