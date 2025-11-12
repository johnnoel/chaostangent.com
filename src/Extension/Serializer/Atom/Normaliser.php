<?php

declare(strict_types=1);

namespace App\Extension\Serializer\Atom;

use App\Entity\Comment;
use App\Entity\Post;
use App\Post\Feed;
use App\Post\FeedUrlGenerator;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly final class Normaliser implements NormalizerInterface
{
    public function __construct(
        private string $title,
        private string $subtitle,
        private string $generatorName,
        private FeedUrlGenerator $urlGenerator,
    ) {
    }

    /** @inheritdoc */
    #[\Override]
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        if (!($data instanceof Feed)) {
            throw new InvalidArgumentException('$data must be an instance of ' . Feed::class);
        }

        if ($data->items instanceof Post) {
            return $this->getCommentsFeed($data->items);
        }

        return $this->getPostsFeed($data->items);
    }

    /** @inheritdoc */
    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Feed && $format === 'atom';
    }

    /** @inheritdoc */
    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [
            Feed::class => true,
        ];
    }

    /**
     * @param array<Post> $posts
     * @return array<string,mixed>
     */
    private function getPostsFeed(array $posts): array
    {
        $updated = (count($posts) > 0) ?
            max(array_map(fn (Post $p): DateTimeImmutable => $p->getUpdated(), $posts)) :
            new DateTimeImmutable('now')
        ;

        return [
            '@xmlns' => 'http://www.w3.org/2005/Atom',
            '@xmlns:thr' => 'http://purl.org/syndication/thread/1.0',
            'title' => [
                '@type' => 'text',
                '#' => $this->title,
            ],
            'subtitle' => [
                '@type' => 'text',
                '#' => $this->subtitle,
            ],
            'updated' => $updated->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            'link' => [
                [
                    '@href' => $this->urlGenerator->getHomeUrl(),
                    '@rel' => 'alternate',
                    '@type' => 'text/html',
                ],
                [
                    '@href' => $this->urlGenerator->getAtomUrl(),
                    '@rel' => 'self',
                    '@type' => 'application/atom+xml',
                ],
            ],
            'id' => $this->urlGenerator->getAtomUrl(),
            'generator' => [
                '@uri' => $this->urlGenerator->getHomeUrl(),
                '#' => $this->generatorName,
            ],
            'entry' => $posts,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function getCommentsFeed(Post $post): array
    {
        $comments = $post->getComments()->toArray();
        $updated = (count($comments) > 0) ?
            max(array_map(fn (Comment $comment): DateTimeImmutable => $comment->getCreated(), $comments)) :
            new DateTimeImmutable('now')
        ;

        return [
            '@xmlns' => 'http://www.w3.org/2005/Atom',
            '@xmlns:thr' => 'http://purl.org/syndication/thread/1.0',
            'title' => [
                '@type' => 'text',
                '#' => 'Responses to : ' . $post->getFullTitle(),
            ],
            'subtitle' => [
                '@type' => 'text',
                '#' => $this->subtitle,
            ],
            'updated' => $updated->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            'link' => [
                [
                    '@href' => $this->urlGenerator->getPostCommentsRssUrl($post),
                    '@rel' => 'alternate',
                    '@type' => 'text/html',
                ],
                [
                    '@href' => $this->urlGenerator->getPostCommentsAtomUrl($post),
                    '@rel' => 'self',
                    '@type' => 'application/atom+xml',
                ],
            ],
            'id' => $this->urlGenerator->getPostCommentsAtomUrl($post),
            'generator' => [
                '@uri' => $this->urlGenerator->getHomeUrl(),
                '#' => $this->generatorName,
            ],
            'entry' => $comments,
        ];
    }
}
