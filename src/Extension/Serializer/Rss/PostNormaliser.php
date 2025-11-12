<?php

declare(strict_types=1);

namespace App\Extension\Serializer\Rss;

use App\Entity\Post;
use App\Post\FeedUrlGenerator;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Twig\Environment;

// minify generated html
final readonly class PostNormaliser implements NormalizerInterface
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
        $pubDate = $post->getDate() ?? new DateTimeImmutable('now');

        return [
            'title' => $post->getFullTitle(),
            'link' => $this->urlGenerator->getPostUrl($post),
            'comments' => $this->urlGenerator->getPostCommentsUrl($post),
            'pubDate' => $pubDate->setTimezone(new DateTimeZone('UTC'))->format('D, d M Y H:i:s O'),
            'dc:creator' => $post->getAuthor(),
            'category' => $post->getDistinctCategoryAndTagNames(),
            'guid' => [
                '@isPermaLink' => 'false',
                '#' => $this->urlGenerator->getPostUrl($post),
            ],
            'description' => $this->urlGenerator->convertLinksToAbsolute(
                $this->twig->createTemplate($post->getSummary() ?? '')->render()
            ),
            'content:encoded' => $this->urlGenerator->convertLinksToAbsolute(
                $this->twig->createTemplate($post->getContent())->render()
            ),
            'wfw:commentRss' => $this->urlGenerator->getPostCommentsRssUrl($post),
            'slash:comments' => $post->getComments()->count(),
        ];
    }

    /** @inheritdoc */
    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Post && $format === 'rss';
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
