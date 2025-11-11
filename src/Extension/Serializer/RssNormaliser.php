<?php

declare(strict_types=1);

namespace App\Extension\Serializer;

use App\Entity\Comment;
use App\Entity\Post;
use App\Post\Feed;
use App\Post\FeedUrlGenerator;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly final class RssNormaliser implements NormalizerInterface
{
    public function __construct(
        private string $title,
        private string $description,
        private string $generator,
        private string $language,
        private string $updatePeriod,
        private string $updateFrequency,
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

        return [
            '@xmlns:content' => 'http://purl.org/rss/1.0/modules/content/',
            '@xmlns:wfw' => 'http://wellformedweb.org/CommentAPI/',
            '@xmlns:dc' => 'http://purl.org/dc/elements/1.0/',
            '@xmlns:atom' => 'http://www.w3.org/2005/Atom',
            '@xmlns:slash' => 'http://purl.org/rss/1.0/modules/slash/',
            '@xmlns:sy' => 'http://purl.org/rss/1.0/modules/syndication/',
            '@version' => '2.0',
            'channel' => [ $this->getChannel($data) ],
        ];
    }

    /** @inheritdoc */
    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Feed && $format === 'rss';
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
     * @return array<string,mixed>
     */
    private function getChannel(Feed $feed): array
    {
        $channel = [
            'description' => $this->description,
            'language' => $this->language,
            'sy:updatePeriod' => $this->updatePeriod,
            'sy:updateFrequency' => $this->updateFrequency,
            'generator' => $this->generator,
        ];

        if ($feed->items instanceof Post) {
            return array_merge($channel, $this->getCommentsChannel($feed->items));
        }

        return array_merge($channel, $this->getPostsChannel($feed->items));
    }

    /**
     * @param array<Post> $posts
     * @return array<string,mixed>
     */
    private function getPostsChannel(array $posts): array
    {
        $lastBuildDate = (count($posts) > 0) ?
            max(array_map(fn (Post $p) => $p->getUpdated(), $posts)) :
            new DateTimeImmutable('now')
        ;

        return [
            'title' => $this->title,
            'lastBuildDate' => $lastBuildDate->setTimezone(new DateTimeZone('UTC'))->format('D, d M Y H:i:s O'),
            'atom:link' => [
                '@href' => $this->urlGenerator->getRssUrl(),
                '@rel' => 'self',
                '@type' => 'application/rss+xml',
            ],
            'link' => $this->urlGenerator->getHomeUrl(),
            'item' => $posts,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function getCommentsChannel(Post $post): array
    {
        $comments = $post->getComments()->toArray();
        $lastBuildDate = (count($comments) > 0) ?
            max(array_map(fn (Comment $c): DateTimeImmutable => $c->getCreated(), $comments)) :
            new DateTimeImmutable('now')
        ;

        return [
            'title' => 'Response to : ' . $post->getFullTitle(),
            'lastBuildDate' => $lastBuildDate->setTimezone(new DateTimeZone('UTC'))->format('D, d M Y H:i:s O'),
            'atom:link' => [
                '@href' => $this->urlGenerator->getPostCommentsRssUrl($post),
                '@rel' => 'self',
                '@type' => 'application/rss+xml',
            ],
            'link' => $this->urlGenerator->getPostUrl($post),
            'item' => $comments,
        ];
    }
}
