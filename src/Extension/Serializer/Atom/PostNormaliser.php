<?php

declare(strict_types=1);

namespace App\Extension\Serializer\Atom;

use App\Entity\Post;
use App\Post\FeedUrlGenerator;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Twig\Environment;

readonly final class PostNormaliser implements NormalizerInterface
{
    public function __construct(
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
        $updated = $post->getUpdated()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
        $published = ($post->getDate() ?? new DateTimeImmutable('now'));
        $published = $published->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');

        return [
            'title' => [
                '@type' => 'html',
                '#' => $post->getFullTitle(),
            ],
            'link' => [
                [
                    '@href' => $this->urlGenerator->getPostUrl($post),
                    '@rel' => 'alternate',
                    '@type' => 'text/html',
                ],
                [
                    '@href' => $this->urlGenerator->getPostCommentsUrl($post),
                    '@rel' => 'replies',
                    '@type' => 'text/html',
                ],
                [
                    '@href' => $this->urlGenerator->getPostCommentsAtomUrl($post),
                    '@rel' => 'replies',
                    '@type' => 'application/atom+xml',
                ],
            ],
            'id' => $this->urlGenerator->getPostUrl($post),
            'updated' => $updated,
            'published' => $published,
            'category' => array_map(fn (string $n): array => ([
                '@scheme' => $this->urlGenerator->getHomeUrl(),
                '@term' => $n,
            ]), $post->getDistinctCategoryAndTagNames()),
            'summary' => [
                '@type' => 'html',
                '#' => $this->urlGenerator->convertLinksToAbsolute(
                    $this->twig->createTemplate($post->getSummary() ?? '')->render()
                ),
            ],
            'content' => [
                '@type' => 'html',
                '#' => $this->urlGenerator->convertLinksToAbsolute(
                    $this->twig->createTemplate($post->getContent())->render()
                ),
            ],
            'thr:total' => $post->getComments()->count(),
            'author' => [
                'name' => $post->getAuthor(),
                'uri' => $this->urlGenerator->getHomeUrl(),
            ],
        ];
    }

    /** @inheritdoc */
    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Post && $format === 'atom';
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
